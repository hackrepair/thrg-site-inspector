# The Hack Repair Guy's Site Inspector: project notes

Developer notes for contributors. For what the plugin does, see [README.md](README.md "What the plugin does and how to install it").

Current release: **2.3.1**, tag `v2.3.1`, published October 25, 2026.

## Scope

A targeted, read-only inspection of conditions that may enable exploitation, plus the original Site Health controls. It does not classify files or activity as malicious, fix anything, simulate exploits, or assign a security score. Every observation carries its context and uncertainty. Reports are kept on the site only, unless an administrator emails one.

## Architecture

- `index.php`: plugin header, Site Health controls (seven toggles, exemptions, Uninstall option), settings page, Plugins-screen links and View details (built from the installed readme), one-time notice. The plugin slug is its installed folder name (`thrg_si_slug()`), so earlier installs under another folder name keep working. The settings option stays `thrg_shc_options`.
- `includes/class-thrg-site-inspector.php`: Tools > Site Inspector, admin-post handlers (delete, clear, pause/resume, email), pure `evaluate()` separated from `collect()`, link-token system with a strict allowlist (`href()`), plain-text report.
- `includes/class-thrg-inspection-evidence.php`: bounded, read-only collectors (accounts, uploads, sensitive files, software, must-use code, salts). No file contents are read, symlinks are skipped, and results say when coverage is partial.
- `includes/class-thrg-si-history.php`: report history in one non-autoloaded option (25 reports, about 600 KB cap, identical results within five minutes saved once).
- `includes/class-thrg-si-email.php`: emails a saved report to chosen administrators as a "state of security" briefing. Pure builders (`subject()`, `narrative()`, `change_line()`, `html()`, `text()`) plus `recipients()` and `send()`. Recipients are looked up server-side from administrator user IDs, never typed in; one personal email each via `wp_mail()` with a plain-text alternative; usernames masked; Reply-To is the sender; one send per user per minute. Handler: `admin_post_thrg_si_email_report` (manage_options + nonce).
- `includes/view-inspector.php`, `assets/inspector.css`: the interface. Shared by the Site Health Controls page, modeled on [The Hack Repair Guy's Plugin Detective](https://github.com/hackrepair/hackrepair-plugin-detective "Plugin Detective on GitHub"). The mascot uses the updated 2026 THRG heart artwork.
- `includes/class-thrg-si-updater.php` and `plugin-update-checker/`: GitHub release updates, independent of the inspector. A release needs tag `vX.Y.Z` and an uploaded `thrg-site-inspector.X.Y.Z.zip` whose top-level folder is `thrg-site-inspector/`.
- `includes/class-thrg-si-update-notices.php`: plain-language messages when a manual update check fails, including a confirmed GitHub rate limit; technical details stay in a disclosure.
- `includes/companion-plugins.php`: adds the **Other THRG Plugins** tab (five companion tools) to this plugin's native View Details dialog.
- `uninstall.php`: removes settings, report history, the history-paused flag, the notice dismissal and update-checker leftovers only when *Delete all plugin data* is ticked.

## Design rules

- Collection is bounded (200 privileged accounts, newest first; 20,000 upload entries, five seconds, 50 results) and read-only. No outbound requests, scheduled jobs, file writes or remediation handlers in the inspector.
- Findings use plain language: why it matters, what was observed, what to do, and the limits of the check. A clean baseline produces zero Review items.
- Output is escaped. Links come from an allowlist: admin pages matching a strict pattern plus two reference URLs. Anything else renders as plain text, and dynamic names cannot create links. Links to other pages open in a new tab (except the way back to Site Inspector from Site Health Controls).
- Reports contain usernames and file names, so the page and docs say to share them only with people you trust. Printing masks usernames.

## Tests

`tests/` covers finding classification, the email builders (subject lines, escaping, masking, plain text), the link allowlist and escaping, the filesystem collectors (against a temporary tree), report history, uninstall behavior, and a smoke test of the Site Health controls. Earlier project notes record PHP 7.4/8.3 and desktop/mobile/print checks. These are historical baseline results, not proof that later changes were exercised across that runtime matrix. Each release section below records its own evidence.

Run the shared PHPCS installation from `/Volumes/BACKUP8TB2/plugins/PHPCS` before committing PHP changes:

```sh
/Volumes/BACKUP8TB2/plugins/PHPCS/run-phpcs.sh "$PWD"
```

The local `phpcs.xml.dist` checks shipped first-party PHP against WordPress Extra and PHPCompatibilityWP for PHP 7.4 and newer. It excludes the test harness, review helpers, and bundled third-party updater. Run it for every PHP change; release sections below record when it passed.

## Third-party code

- `plugin-update-checker/` is [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") 5.7 by Jānis Elsts (MIT license, `plugin-update-checker/license.txt`). It is the unmodified upstream copy, kept whole on purpose so it can be replaced by a straight folder swap when a new version is released. Do not edit or trim it. Check the upstream releases page whenever this plugin is released.

## Limits and open work

- No public-exposure verification, advisory feed, custom signup flow inspection, malware scan or server enforcement test. Permission bits do not establish ACLs or exploitability; HTTPS settings do not establish redirects or certificate validity.
- Multisite behavior and cross-version compatibility need dedicated tests.
- Inspector strings are not yet translatable.
- `PHP_SUPPORT` dates are maintained by hand. Add new PHP branches as they ship, and expect each branch to move from Review to Potential weakness on its end date (8.2 on January 1, 2027).

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

## Release 2.3.0: Support container

Added a Support container (anchor `#support`) at the bottom of Tools > Site Inspector, after the report history and above the coverage footer. It carries the buy-Jim-a-coffee link and the GitHub Discussions link, using the same wording as Post Update Email Notifier. Packaged October 20, 2026; no inspection, history, email or updater changes.

## Release 2.2.6: portable ZIP permissions

A live installation returned HTTP 403 for assets/inspector.css because the 2.2.5 ZIP retained its source mode 0600. The builder now explicitly emits Unix regular files with mode 0644 for every package entry, including bundled dependencies, independently of source permissions. The build verifies modes and byte-for-byte source correspondence. tests/package-permissions.py exercises mode-0600 source files in an isolated fixture. Previous release ZIPs are preserved. Consider the existing Codex Security review reminder separately.

## Community support

Questions, ideas and community help: https://github.com/hackrepair/thrg-site-inspector/discussions. Reproducible bugs: https://github.com/hackrepair/thrg-site-inspector/issues. Security reports stay private. GitHub Discussions is enabled; Reddit is no longer the support destination. Documentation and support-link updates are committed for future packages without replacing published release assets.

## Project page (GitHub Pages), October 25, 2026

https://hackrepair.github.io/thrg-site-inspector/ is a one-page site served by GitHub Pages from `docs/` on `main` (`docs/index.html`, `docs/img/`, `docs/video/`, `docs/.nojekyll`). It has a hero with an animated sample report, the click-to-load intro video, download buttons, the four "open doors", the check list, the three main features, a read-only and privacy band, Site Health Controls, install steps and project links. On load it asks the GitHub API for the latest release and points every download button at that zip, labeled with the version; if the lookup fails the buttons go to the latest release page. Fonts: Bricolage Grotesque and Atkinson Hyperlegible from Google Fonts, matching the Stickies page. Images in `docs/img/` are copies of plugin assets; refresh them if the brand art changes.

The intro video (`docs/video/site-inspector-intro.mp4`, 75 seconds, 1080p, with Jim's voiceover) is an animated mock-up with sample data (example.com), not a recording of a real site. Its source, the voiceover script and audio live outside the repo in `../video-intro/`; `source/render-vo.js` retimes the animation to the recorded voiceover. It is published on YouTube at https://www.youtube.com/watch?v=ruIz3vJbSUM. The page sets `YOUTUBE_ID` to that video, so its click-to-load player uses youtube-nocookie; the bundled MP4 stays as the fallback if `YOUTUBE_ID` is cleared. The README thumbnail (`docs/img/video-thumb.jpg`) links to the YouTube video.

Release rule: `docs/` is not part of the plugin. `scripts/build-release.py` already skips it.


## Companion-plugin guide — October 25, 2026 (released in 2.3.1)

The native View Details **Other THRG Plugins** tab lists the five other tools: Disable Other Plugin Notices, Group Password Reset, Plugin Detective, Post Update Email Notifier and Plugin Archiver. The same guide in each sibling plugin lists Site Inspector and excludes itself. Plugin Archiver always links to https://wordpress.org/plugins/hackrepair-plugin-archiver/; the GitHub-distributed tools link to their own repositories. Cards are informational links, with no automatic installs or external requests. Archiver retains WordPress.org updates and metadata. Shipped in 2.3.1.

Verification: shared PHPCS and `php tests/companion-plugins.php` pass for this checkout. Native WordPress View Details on Zendogs renders five distinct companion cards, with no self-link and Archiver linked only to WordPress.org. Archiver uses `plugins_api_result` for official metadata and preserves ratings/version/update controls; the test used a temporary loader beside the older installed Archiver, not a full upgrade. Temporary preview files are restored after testing; no reset, archive, delete or email action was performed. Published releases remain unchanged.


## 2.3.1 release preparation — October 25, 2026

User approved packaging and publication for the five GitHub-updated plugins. Plugin Archiver is explicitly excluded from this release pass. Version headers/constants, stable tag, README and release notes are aligned at 2.3.1. Release checks and publication results follow below.


## 2.3.1 published and verified — October 25, 2026

Published: https://github.com/hackrepair/thrg-site-inspector/releases/tag/v2.3.1. Version metadata, ZIP and published digest agree. Real Zendogs updater discovery/install, retained settings/activation, installed file hashes and native View Details verified. All local checks passed; CI scope and hashes are recorded in `docs/release-verification-2.3.1.md`. Older packages/releases retained. Plugin Archiver was excluded from this release pass.


## Security reporting standard — October 26, 2026

Added the common SECURITY.md policy, repository-specific private reporting destination, fallback email, qualified response targets and product limits. GitHub private reporting is enabled and API-verified. The reusable verifier and `docs/security-reporting-verification.md` describe repeat checks and separate API, browser, inbox and application-security evidence. The maintainer browser forms are now verified for all six repositories; outside-reporter access and fallback mailbox delivery remain untested. See `docs/security-reporting-verification-2026-10-26.md`. This updates repository policy/docs and packaging exclusions only; no plugin version or release changes.


## Secret protection and dependency coverage — October 26, 2026

Enabled secret scanning, push protection and Dependabot alerts; added the trusted-main dependency inventory workflow and live read-only protection verifier. This is repository tooling, with no plugin release/SVN/runtime change. Bundled Parsedown declares 1.6.0, with upstream advisories requiring source-aware review. See `docs/repository-protection-2026-10-26.md` and `docs/dependency-coverage.md` for evidence and limits.

## Review fixes — October 26, 2026 (not yet released)

A full review of 2.3.1 found no security bugs. These fixes are in source for the next patch release:

- Accounts: the collector now fetches only accounts with a watched capability (`capability__in`), newest first, so the 200-account limit can no longer hide a recently added administrator behind older ordinary users.
- PHP support table: added PHP 8.5 (security support until 2029-12-31).
- Uninstall: when *Delete all plugin data* is ticked on any site, also removes the update checker's `external_updates-<folder>` site option and the `thrg_si_github_limit` site transient. The update checker already clears its cron hook on deactivation.
- Email: Reply-To is built by `THRG_SI_Email::reply_to()`, which quotes the display name and strips characters `wp_mail()` treats as separators.
- View details: Requires at least, Tested up to and Requires PHP are read from the installed readme header instead of being repeated in code. `last_updated` is no longer sent, because the installed files only know their unpack time.
- Email form: non-scalar `recipients[]` values are dropped before conversion to user IDs.
- Update details: `THRG_SI_Updater::clean_info()` sanitizes the text sections the update checker builds from GitHub release notes (see the Parsedown assessment in `docs/dependency-coverage.md`). Covered by `tests/updater-sanitize.php`.
- Docs: current-release line, Architecture list, stale 2.2.4 notes, companion-guide status and version-specific subject wording brought up to date.

Tests: new checks for the account query, PHP 8.5, Reply-To, uninstall leftovers, readme metadata (including readme/header agreement) and update-details sanitizing. All `tests/` pass.
