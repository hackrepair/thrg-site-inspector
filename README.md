# The Hack Repair Guy's Site Inspector

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next.

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those in a few seconds, explains what it found in plain language, and never changes anything on your site.

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

**Report history.** Every inspection is saved on your own site (the 25 most recent), with what is new and what is resolved since the previous report. Reopen, delete, clear or pause history at any time.

**Share a report.** Copy it as plain text, download it, or print it (usernames are masked on paper), or email a plain-language "state of security" briefing to some or all of the site's administrators, with a subject line that says where things stand.

**Settings → Site Health Controls** keeps the original feature: switch off the WordPress Site Health menu item, screen redirect, dashboard widget, background tests, weekly cron check, REST routes and AJAX actions, with administrator and user ID exemptions. It also has an opt-in *Delete all plugin data* setting for uninstall.

## Privacy and scope

The inspector is read-only. It makes no changes, sends no network requests and reads no file contents. Reports are stored only in your site's database and include usernames and file names. No report leaves the site unless an administrator chooses to email it, and emails can only go to the site's own administrators. The only other outside request is WordPress's normal update check against this plugin's GitHub releases.

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
```

See [PROJECT.md](PROJECT.md "Architecture, design decisions and known limits") for the architecture, design decisions and known limits.

## License and credits

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2"). Started from a 2019 plugin by Louis Fico, used under the GPLv2-or-later license; the original attribution is retained.

Update checks use [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") by Jānis Elsts (MIT license), included unmodified.

Made by [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair").
