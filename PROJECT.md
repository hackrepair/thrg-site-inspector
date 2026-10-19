# The Hack Repair Guy's Site Inspector: project notes

Developer notes for contributors. For what the plugin does, see [README.md](README.md "What the plugin does and how to install it").

Current release: **2.2.4**, tag `v2.2.4`, approved for publication October 18, 2026.

## Scope

A targeted, read-only inspection of conditions that may enable exploitation, plus the original Site Health controls. It does not classify files or activity as malicious, fix anything, simulate exploits, or assign a security score. Every observation carries its context and uncertainty. Reports are kept on the site only, unless an administrator emails one.

## Architecture

- `index.php`: plugin header, Site Health controls (seven toggles, exemptions, Uninstall option), settings page, Plugins-screen links and View details, one-time notice. The plugin slug is its installed folder name (`thrg_si_slug()`), so earlier installs under another folder name keep working. The settings option stays `thrg_shc_options`.
- `includes/class-thrg-site-inspector.php`: Tools > Site Inspector, admin-post handlers (delete, clear, pause), pure `evaluate()` separated from `collect()`, link-token system with a strict allowlist (`href()`), plain-text report.
- `includes/class-thrg-inspection-evidence.php`: bounded, read-only collectors (accounts, uploads, sensitive files, software, must-use code, salts). No file contents are read, symlinks are skipped, and results say when coverage is partial.
- `includes/class-thrg-si-history.php`: report history in one non-autoloaded option (25 reports, about 600 KB cap, identical results within five minutes saved once).
- `includes/class-thrg-si-email.php`: emails a saved report to chosen administrators as a "state of security" briefing. Pure builders (`subject()`, `narrative()`, `change_line()`, `html()`, `text()`) plus `recipients()` and `send()`. Recipients are looked up server-side from administrator user IDs, never typed in; one personal email each via `wp_mail()` with a plain-text alternative; usernames masked; Reply-To is the sender; one send per user per minute. Handler: `admin_post_thrg_si_email_report` (manage_options + nonce).
- `includes/view-inspector.php`, `assets/inspector.css`: the interface. Shared by the Site Health Controls page, modeled on [The Hack Repair Guy's Plugin Detective](https://github.com/hackrepair/hackrepair-plugin-detective "Plugin Detective on GitHub"). The mascot uses the updated 2026 THRG heart artwork.
- `includes/class-thrg-si-updater.php` and `plugin-update-checker/`: GitHub release updates, independent of the inspector. A release needs tag `vX.Y.Z` and an uploaded `thrg-site-inspector.X.Y.Z.zip` whose top-level folder is `thrg-site-inspector/`.
- `uninstall.php`: removes settings, report history and notice dismissal only when *Delete all plugin data* is ticked.

## Design rules

- Collection is bounded (200 accounts; 20,000 upload entries, five seconds, 50 results) and read-only. No outbound requests, scheduled jobs, file writes or remediation handlers in the inspector.
- Findings use plain language: why it matters, what was observed, what to do, and the limits of the check. A clean baseline produces zero Review items.
- Output is escaped. Links come from an allowlist: admin pages matching a strict pattern plus two reference URLs. Anything else renders as plain text, and dynamic names cannot create links. Links to other pages open in a new tab (except the way back to Site Inspector from Site Health Controls).
- Reports contain usernames and file names, so the page and docs say to share them only with people you trust. Printing masks usernames.

## Tests

`tests/` covers finding classification, the email builders (subject lines, escaping, masking, plain text), the link allowlist and escaping, the filesystem collectors (against a temporary tree), report history, uninstall behavior, and a smoke test of the Site Health controls. Earlier project notes record PHP 7.4/8.3 and desktop/mobile/print checks. These are historical baseline results, not proof that the current 2.2.4 changes were exercised across that runtime matrix. The 2.2.4 evidence is listed below.

Run the shared PHPCS installation from `/Volumes/BACKUP8TB2/plugins/PHPCS` before committing PHP changes:

```sh
/Volumes/BACKUP8TB2/plugins/PHPCS/run-phpcs.sh "$PWD"
```

The local `phpcs.xml.dist` checks shipped first-party PHP against WordPress Extra and PHPCompatibilityWP for PHP 7.4 and newer. It excludes the test harness, review helpers, and bundled third-party updater. The current 2.2.4 email changes passed this check on October 18, 2026.

## Third-party code

- `plugin-update-checker/` is [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") 5.7 by Jānis Elsts (MIT license, `plugin-update-checker/license.txt`). It is the unmodified upstream copy, kept whole on purpose so it can be replaced by a straight folder swap when a new version is released. Do not edit or trim it. Check the upstream releases page whenever this plugin is released.

## Limits and open work

- No public-exposure verification, advisory feed, custom signup flow inspection, malware scan or server enforcement test. Permission bits do not establish ACLs or exploitability; HTTPS settings do not establish redirects or certificate validity.
- Multisite behavior and cross-version compatibility need dedicated tests.
- Inspector strings are not yet translatable.
- For the pending 2.2.4 build, published-updater discovery/install continuity and a final security review have not been verified. Consider the Codex Security diff-review reminder; do not run that plugin automatically.

## 2.2.4 email format update — October 18, 2026

Local development build, not published. Use `[domain] [Site Inspector] Security Check: …` in subjects while preserving existing status/change-count wording. Subject preview and outgoing email use the current configured home URL domain (subdomains and explicit ports retained; scheme/path omitted; site URL fallback). HTML email uses the full available width without the former 600px maximum. Existing report history, branding, recipient handling, masking and plain-text alternatives are preserved.

The completed focused checks cover subject states, domain/port normalization, HTML escaping, masking, full-width output, and shared PHPCS. Consider the existing Codex Security diff-review reminder; no automatic security scan is requested.

Validation: email builder, report-history, and updater-message tests passed; shared PHPCS, changed-PHP syntax, diff whitespace and package integrity checks passed. Installed the 2.2.4 review ZIP on Zendogs. A clearly labeled sample email built with the updated template was captured in Mailpit with HTML/plain-text parts and the new domain/plugin subject tags. Desktop and 390px browser rendering had no horizontal overflow. No inspection was run and no report history was created. External inbox delivery and other email clients were not tested. No release was published.

## Current email workflow and limits

- UI labels: Send this report to your designer or developer → Email to administrators → All administrators / individual recipients → optional note → Subject → Send email; pending state is Sending….
- Up to the first 25 administrator accounts by display name are considered; valid-address recipients are selected by default and checked again on send. Notes are limited to 1,000 characters.
- A saved report, manage_options and the email nonce are required. After any accepted message, a per-sender 60-second throttle applies. Acceptance is not confirmed delivery.
- HTML and plain-text alternative share the report content. Up to three evidence lines and eight table rows per finding are included in email; usernames are partly masked; the full retained report is linked. Reply-To uses the sender's valid email address.
- Report history is bounded by 25 entries and 600,000 bytes, with identical results within 300 seconds deduplicated. Retention is unrelated to the 25-recipient email limit.

Documentation audit on October 18, 2026: README, packaged readme, project notes and pending release notes were checked against UI labels, mail builders, send guards and history constants. Clarified that inspection is read-only while report storage and Site Health settings write local data. Compatibility claims distinguish historical baseline checks from the current local review. No release publication was requested by this audit.

Header correction: the admin-screen version label now sits beside Site Inspector in the heading, using small regular-weight text, rather than following the descriptive byline.

Email feedback below the administrator form now reports both counts, for example: **Email accepted for sending: 1. Failed: 0. Acceptance does not confirm delivery.** Partial and complete failures retain setup guidance. Counts are display-only values returned after the send; they do not authorize sending.

## 2.2.4 release approval

User approved versioning, packaging and publication on October 18, 2026. Earlier local-review/no-release statements above describe the development stage. The release includes subject tags, full-width HTML, version placement, accepted/failed feedback, and the documentation audit. Final scope and validation limits: releases/v2.2.4.md.

## Animated branding

The 2026 heart logo now appears in the main inspector and Site Health Controls headers, with two gentle heartbeat cycles (4.4 seconds total). Native View Details uses the same logo on a warm dark banner with a wrapping title. Reduced-motion preferences and printing disable animation. Assets are local PNG/CSS, loaded only on this plugin’s screens or matching details dialog; no JavaScript or external service is needed. The help-card image uses the updated artwork without animation. Reusable source kit: `/Volumes/BACKUP8TB2/plugins/hackrepair-with-heart-animated-2026/plugin-kit`. Included in release 2.2.5.

Branding preview validation: shared PHPCS passed (11 files), existing smoke tests passed, and Zendogs main page/native View Details were checked in the browser. Details fit 772px and 390px widths; both placements disable animation under emulated reduced motion, and the computed CSS confirms a finite two-cycle animation. Group Password Reset’s details dialog did not load Site Inspector branding styles. Source and Zendogs only; no new plugin release, tag, commit or push. Runtime backup: `/tmp/thrg-si-before-brand-preview.tar.gz` inside `zendogs-wp-sandbox-wordpress-1`.

Release 2.2.5: refreshed 2026 THRG heart branding, two heartbeat cycles on opening, reduced-motion/print support, and a wrapping native View Details banner. Assets are bundled locally. Consider the Codex Security review reminder before future changes.
