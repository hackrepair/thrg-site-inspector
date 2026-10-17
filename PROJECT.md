# The Hack Repair Guy's Site Inspector: project notes

Developer notes for contributors. For what the plugin does, see [README.md](README.md "What the plugin does and how to install it").

## Scope

A targeted, read-only inspection of conditions that may enable exploitation, plus the original Site Health controls. It does not classify files or activity as malicious, fix anything, simulate exploits, or assign a security score. Every observation carries its context and uncertainty. Reports are kept on the site only, unless an administrator emails one.

## Architecture

- `index.php`: plugin header, Site Health controls (seven toggles, exemptions, Uninstall option), settings page, Plugins-screen links and View details, one-time notice. The plugin slug is its installed folder name (`thrg_si_slug()`), so earlier installs under another folder name keep working. The settings option stays `thrg_shc_options`.
- `includes/class-thrg-site-inspector.php`: Tools > Site Inspector, admin-post handlers (delete, clear, pause), pure `evaluate()` separated from `collect()`, link-token system with a strict allowlist (`href()`), plain-text report.
- `includes/class-thrg-inspection-evidence.php`: bounded, read-only collectors (accounts, uploads, sensitive files, software, must-use code, salts). No file contents are read, symlinks are skipped, and results say when coverage is partial.
- `includes/class-thrg-si-history.php`: report history in one non-autoloaded option (25 reports, about 600 KB cap, identical results within five minutes saved once).
- `includes/class-thrg-si-email.php`: emails a saved report to chosen administrators as a "state of security" briefing. Pure builders (`subject()`, `narrative()`, `change_line()`, `html()`, `text()`) plus `recipients()` and `send()`. Recipients are looked up server-side from administrator user IDs, never typed in; one personal email each via `wp_mail()` with a plain-text alternative; usernames masked; Reply-To is the sender; one send per user per minute. Handler: `admin_post_thrg_si_email_report` (manage_options + nonce).
- `includes/view-inspector.php`, `assets/inspector.css`: the interface. Shared by the Site Health Controls page, modeled on [Plugin Detective](https://wordpress.org/plugins/plugin-detective/ "Plugin Detective on WordPress.org"). The mascot image is reused from Plugin Detective.
- `includes/class-thrg-si-updater.php` and `plugin-update-checker/`: GitHub release updates, independent of the inspector. A release needs tag `vX.Y.Z` and an uploaded `thrg-site-inspector-X.Y.Z.zip` whose top-level folder is `thrg-site-inspector/`.
- `uninstall.php`: removes settings, report history and notice dismissal only when *Delete all plugin data* is ticked.

## Design rules

- Collection is bounded (200 accounts; 20,000 upload entries, five seconds, 50 results) and read-only. No outbound requests, scheduled jobs, file writes or remediation handlers in the inspector.
- Findings use plain language: why it matters, what was observed, what to do, and the limits of the check. A clean baseline produces zero Review items.
- Output is escaped. Links come from an allowlist: admin pages matching a strict pattern plus two reference URLs. Anything else renders as plain text, and dynamic names cannot create links. Links to other pages open in a new tab (except the way back to Site Inspector from Site Health Controls).
- Reports contain usernames and file names, so the page and docs say to share them only with people you trust. Printing masks usernames.

## Tests

`tests/` covers finding classification, the email builders (subject lines, escaping, masking, plain text), the link allowlist and escaping, the filesystem collectors (against a temporary tree), report history, uninstall behavior, and a smoke test of the Site Health controls. All pass on PHP 7.4 and 8.3. The interface has been checked in a browser on desktop, mobile (no horizontal overflow) and print layouts.

## Third-party code

- `plugin-update-checker/` is [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") 5.7 by Jānis Elsts (MIT license, `plugin-update-checker/license.txt`). It is the unmodified upstream copy, kept whole on purpose so it can be replaced by a straight folder swap when a new version is released. Do not edit or trim it. Check the upstream releases page whenever this plugin is released.

## Limits and open work

- No public-exposure verification, advisory feed, custom signup flow inspection, malware scan or server enforcement test. Permission bits do not establish ACLs or exploitability; HTTPS settings do not establish redirects or certificate validity.
- Multisite behavior and cross-version compatibility need dedicated tests.
- Inspector strings are not yet translatable.
- Update continuity (an actual update without deactivation) and a final security review of the release package are still to do before a tagged release.
