#!/usr/bin/env python3
"""Build and validate a complete ZIP before replacing the last good artifacts."""
import argparse
import fnmatch
import hashlib
import os
from pathlib import Path
import re
import shutil
import tempfile
import zipfile


def publish(staged, destinations):
    """Restore the previous ZIP/checksum pair if either replacement fails."""
    backups = []
    for index, destination in enumerate(destinations):
        backup = staged[0].parent / ('previous-' + str(index))
        if destination.exists():
            shutil.copy2(destination, backup)
            backups.append(backup)
        else:
            backups.append(None)
    replaced = []
    try:
        for source, destination in zip(staged, destinations):
            os.replace(source, destination)
            replaced.append(destination)
    except OSError:
        for destination, backup in zip(destinations, backups):
            if destination in replaced:
                if backup is None:
                    destination.unlink(missing_ok=True)
                else:
                    os.replace(backup, destination)
        raise


def build(root, output=None):
    main = next(f for f in root.glob('*.php') if re.search(r'Plugin Name:', f.read_text()))
    slug = main.stem if main.stem != 'index' else root.name
    version = re.search(r'Version:\s*([0-9.]+)', main.read_text()).group(1)
    output = output or root / 'dist' / (slug + '.' + version + '.zip')
    output = output.resolve()
    checksum = output.with_suffix(output.suffix + '.sha256')
    required = root / 'includes/companion-plugins.php'
    if not required.is_file():
        raise RuntimeError('Required companion guide is missing; previous artifacts were kept.')
    excluded = {'.git', '.github', '.review', 'tests', 'scripts', 'dist', 'releases', 'node_modules', 'vendor', 'docs', '.wordpress-org'}
    excluded_files = {'.DS_Store', '.gitignore', '.editorconfig', '.distignore', 'phpcs.xml.dist', 'README.md', 'PROJECT.md', 'PROJECT_UPDATES.md', 'SECURITY.md'}
    ignore = root / '.distignore'
    patterns = [s.strip().lstrip('/') for s in ignore.read_text().splitlines() if s.strip() and not s.startswith('#')] if ignore.exists() else []
    files = []
    for path in root.rglob('*'):
        if not path.is_file() or path.resolve() in (output, checksum):
            continue
        relative = path.relative_to(root)
        if relative.parts[0] != 'plugin-update-checker' and (
            relative.parts[0] in excluded or path.name in excluded_files or
            any(fnmatch.fnmatch(str(relative), pattern) or str(relative).startswith(pattern.rstrip('/') + '/') for pattern in patterns)
        ):
            continue
        files.append(path)
    files.sort()
    output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='.release-build-', dir=output.parent) as temporary:
        archive = Path(temporary) / output.name
        sidecar = Path(temporary) / checksum.name
        with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as package:
            for path in files:
                info = zipfile.ZipInfo.from_file(path, slug + '/' + str(path.relative_to(root)))
                info.create_system = 3
                info.external_attr = 0o100644 << 16
                info.compress_type = zipfile.ZIP_DEFLATED
                package.writestr(info, path.read_bytes())
        with zipfile.ZipFile(archive) as package:
            if package.testzip() is not None:
                raise RuntimeError('ZIP integrity check failed.')
            if any((info.external_attr >> 16) & 0o7777 != 0o644 for info in package.infolist()):
                raise RuntimeError('Packaged files must use 0644 permissions.')
            if {name.split('/')[0] for name in package.namelist()} != {slug}:
                raise RuntimeError('Package must have one expected root.')
            for path in files:
                if package.read(slug + '/' + str(path.relative_to(root))) != path.read_bytes():
                    raise RuntimeError('Source changed during the build: ' + str(path.relative_to(root)))
            for path in (root / 'plugin-update-checker').rglob('*'):
                if path.is_file() and package.read(slug + '/' + str(path.relative_to(root))) != path.read_bytes():
                    raise RuntimeError('Bundled dependency was not preserved.')
            if slug + '/includes/companion-plugins.php' not in package.namelist():
                raise RuntimeError('Required companion guide was excluded.')
        digest = hashlib.sha256(archive.read_bytes()).hexdigest()
        sidecar.write_text(digest + '  ' + output.name + '\n')
        archive.chmod(0o644)
        sidecar.chmod(0o644)
        publish((archive, sidecar), (output, checksum))
    return output, digest


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    output, digest = build(Path(__file__).resolve().parents[1], args.output)
    print(output)
    print(digest)


if __name__ == '__main__':
    main()
