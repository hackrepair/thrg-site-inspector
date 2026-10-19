# The Hack Repair Guy's Site Inspector

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next.

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those in a few seconds, explains what it found in plain language, and does not apply fixes to your site. Reports are saved locally when history is enabled.

Current release: **2.2.4**. See [release notes](releases/v2.2.5.md).

## What it does

**Tools → Site Inspector** checks:

- Updates waiting for WordPress, plugins and themes (from WordPress's own stored update data)
- Administrator accounts, with recent and unusual ones flagged
- PHP files in uploads (tiny placeholder `index.php` files are ignored)
- Backups, `error_log` and debug logs, and leftover `wp-config.php` copies in the web folder
- Must-use plugins and drop-ins
- Unused plugins and themes
- PHP version support, security keys and salts, the built-in code editor, and PHP error display
- Registration privileges, HTTPS settings, and `wp-config.php` permissions

Each finding says why it matters, what was observed, and what to do next, with the limits of the check one click away. Passing checks are collapsed, and most results link to the admin page where you can act on them.

**Report history.** When history is enabled, inspections are saved on your own site (up to 25 reports within a 600,000-byte storage limit), with what is new and what is resolved since the previous report. Reopen, delete, clear or pause history at any time.

**Share a report.** Copy it as plain text, download it, or print it (usernames are masked on paper), or email a plain-language "state of security" briefing to some or all of the site's administrators, with a subject line that says where things stand.

**Settings → Site Health Controls** keeps the original feature: switch off the WordPress Site Health menu item, screen redirect, dashboard widget, background tests, weekly cron check, REST routes and AJAX actions, with administrator and user ID exemptions. It also has an opt-in *Delete all plugin data* setting for uninstall.

## Email a report to administrators

1. Open a saved report. In **Send this report to your designer or developer**, choose **Email to administrators**.
2. Review **All administrators** and the individual recipients. The first 25 administrator accounts by display name are considered, and only those with valid email addresses are shown; available recipients are selected by default. Deselect anyone who should not receive it.
3. Optionally add a personal note (up to 1,000 characters), review **Subject**, and choose **Send email**. A **Sending…** label indicates the request is underway.

Version 2.2.4 uses `[domain] [Site Inspector] Security Check: …` subjects, for example `[example.com] [Site Inspector] Security Check: 2 issues need attention (1 new since last check)`. The status and change counts remain in the subject. Both subject preview and send identify the current home URL domain, including subdomains and explicit local ports; they omit the scheme and path and fall back to the site URL if needed. Existing saved reports are not rewritten.

The branded HTML email fills the available width and includes a plain-text alternative. It has a personal greeting, optional note, report findings and next steps, status counts, comparisons with the previous retained report, and a link to open the saved report (administrator sign-in required). Usernames are partly masked. Evidence is condensed: up to three evidence lines and eight table rows per finding, with the full saved report linked for detail. Each recipient gets a separate message; Reply-To uses the sender's valid email address.

Sending requires `manage_options`, a valid nonce, a retained report, and selected current administrator accounts. Emailing a newly generated report requires history to be enabled and that report saved. After at least one message is accepted, the sender must wait one minute before another send. Feedback can report full or partial failure; acceptance for sending does not guarantee inbox delivery. WordPress uses the site's configured mail transport; SMTP configuration may be needed. Local Mailpit capture verifies local receipt, not external delivery.

## Privacy and scope

The inspection reads local settings and bounded filesystem metadata without applying fixes, making external requests, or reading file contents. Saving report history and changing Site Health Controls do write plugin settings/data. Reports are stored only in your site's database and include usernames and file names. Reports leave the site only when an administrator chooses to export or email them, and emails can only go to the site's own administrators. Plugin update discovery and package downloads contact GitHub independently of inspections. Those requests expose normal connection information, such as the server IP and HTTP headers; report content is not included in update requests.

It is not a malware scanner, firewall, vulnerability-feed client, or a complete replacement for every WordPress diagnostic. No overall security score is assigned, and "nothing found" applies only to the check described. Multisite registration policy is explicitly unverified.

## Install

Upload the plugin ZIP under **Plugins → Add New → Upload Plugin**, or place the `thrg-site-inspector` folder in `wp-content/plugins/`, then activate it and open **Tools → Site Inspector**.

The plugin lives in the `thrg-site-inspector` folder and starts from `index.php`. Updates come from this repository's [GitHub releases](https://github.com/hackrepair/thrg-site-inspector/releases "All Site Inspector releases and download files").

## Development

Requires WordPress 6.9.4+ and PHP 7.4+. Tests are plain PHP scripts:

```
php tests/smoke.php
php tests/inspector.php
php tests/evidence.php
php tests/history.php
php tests/uninstall.php
php tests/email.php
php tests/update-notices.php
```

Run shared PHPCS with `/Volumes/BACKUP8TB2/plugins/PHPCS/run-phpcs.sh "$PWD"`. Consider a Codex Security diff review before release; do not run that tool automatically.

See [PROJECT.md](PROJECT.md "Architecture, design decisions and known limits") for the architecture, design decisions and known limits.

## License and credits

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2"). Started from a 2019 plugin by Louis Fico, used under the GPLv2-or-later license; the original attribution is retained.

Update checks use [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") by Jānis Elsts (MIT license), included unmodified.

Made by [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair").

## 2.2.0

View Details now includes **Other THRG Plugins**, a guide to the three companion tools with descriptions and project links.

## 2.2.1

Corrected release packaging to retain the complete bundled updater, including the View Details parser dependencies.

## 2.2.2

View Details uses the approved compact companion-plugin cards, with content contained beside the sidebar and the installed version displayed.

## 2.2.3

Manual update-check failures now explain that inspection features remain available. Confirmed GitHub rate limits show a retry time in the site's timezone. Technical diagnostics are available in a disclosure rather than the main message. This update changes reporting; retry scheduling is unchanged.

## 2.2.4 email format

Email subjects follow `[domain] [Site Inspector] Security Check: …`, retaining status and new/resolved counts. For example: `[example.com] [Site Inspector] Security Check: 2 issues need attention`. Both the preview and send use the current configured home URL domain, including subdomains and explicit local ports; scheme and path are omitted, with site URL as fallback. Saved report history is not rewritten.

The HTML email fills the available width, matching Group Password Reset’s layout standard. Existing branding, recipient selection, personal notes, username masking, report links and the plain-text alternative remain in place.

Email feedback below the administrator form now reports both counts, for example: **Email accepted for sending: 1. Failed: 0. Acceptance does not confirm delivery.** Partial and complete failures retain setup guidance. Counts are display-only values returned after the send; they do not authorize sending.

## Animated branding

The 2026 heart logo now appears in the main inspector and Site Health Controls headers, with two gentle heartbeat cycles (4.4 seconds total). Native View Details uses the same logo on a warm dark banner with a wrapping title. Reduced-motion preferences and printing disable animation. Assets are local PNG/CSS, loaded only on this plugin’s screens or matching details dialog; no JavaScript or external service is needed. The help-card image uses the updated artwork without animation. Reusable source kit: `/Volumes/BACKUP8TB2/plugins/hackrepair-with-heart-animated-2026/plugin-kit`. Included in release 2.2.5.

Release 2.2.5: refreshed 2026 THRG heart branding, two heartbeat cycles on opening, reduced-motion/print support, and a wrapping native View Details banner. Assets are bundled locally. Consider the Codex Security review reminder before future changes.
