#!/usr/bin/env python3
"""Failed validation and artifact replacement must preserve the last good pair."""
import hashlib
import importlib.util
from pathlib import Path
import shutil
import tempfile
from unittest.mock import patch

source = Path(__file__).resolve().parents[1] / 'scripts/build-release.py'
spec = importlib.util.spec_from_file_location('release_builder', source)
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)
with tempfile.TemporaryDirectory() as temporary:
    root = Path(temporary) / 'fixture'
    (root / 'includes').mkdir(parents=True)
    (root / 'plugin-update-checker').mkdir()
    (root / 'index.php').write_text('<?php /* Plugin Name: Fixture\nVersion: 1.0.0 */')
    companion = root / 'includes/companion-plugins.php'
    companion.write_text('<?php // fixture')
    output, _ = builder.build(root)
    checksum = output.with_suffix('.zip.sha256')
    before = (output.read_bytes(), checksum.read_bytes())
    companion.unlink()
    try:
        builder.build(root)
        raise AssertionError('Missing input was accepted')
    except RuntimeError:
        pass
    assert before == (output.read_bytes(), checksum.read_bytes())
    companion.write_text('<?php // replacement')
    (root / '.distignore').write_text('/includes/companion-plugins.php\n')
    try:
        builder.build(root)
        raise AssertionError('Excluded required input was accepted')
    except RuntimeError:
        pass
    assert before == (output.read_bytes(), checksum.read_bytes())
    (root / '.distignore').unlink()
    replace = builder.os.replace
    def fail_sidecar(src, dst):
        if Path(src).name.endswith('.sha256'):
            raise OSError('injected sidecar replacement failure')
        return replace(src, dst)
    try:
        with patch.object(builder.os, 'replace', side_effect=fail_sidecar):
            builder.build(root)
        raise AssertionError('Injected replacement failure was ignored')
    except OSError:
        pass
    assert before == (output.read_bytes(), checksum.read_bytes())
    assert checksum.read_text().split()[0] == hashlib.sha256(output.read_bytes()).hexdigest()
    assert not list(output.parent.glob('.release-build-*'))
print('PASS missing/excluded required input and sidecar replacement failure preserve the previous ZIP/checksum pair')
