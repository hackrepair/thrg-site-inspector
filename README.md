<p align="center">
  <img src=".github/branding/thrg-heart-animated.gif" alt="The Hack Repair Guy heart and wrench logo" width="144" height="144">
</p>

<h1 align="center">The Hack Repair Guy's Site Inspector</h1>

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next; all in my voice, Jim Walker, The Hack Repair Guy.

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those in a few seconds, explains what it found in plain language, and does not apply fixes to your site. Reports are saved locally when history is enabled.

Version **2.5.0**. See [release notes](releases/v2.5.0.md).

<p align="center">
  <strong>▶ Watch Site Inspector in 75 seconds</strong><br>
  <a href="https://www.youtube.com/watch?v=ruIz3vJbSUM" title="Watch the Site Inspector intro on YouTube (75 seconds)"><img src="docs/img/video-thumb.jpg" alt="The Hack Repair Guy's Site Inspector: watch the 75-second tour on YouTube" width="480"></a>
  <br><br>
  <a href="https://github.com/hackrepair/thrg-site-inspector/releases/latest" title="Download the latest Site Inspector plugin zip from GitHub"><img src="https://img.shields.io/badge/%E2%AC%87%20%20Download%20the%20latest%20release-b34700?style=for-the-badge&labelColor=b34700" alt="Download the latest release" height="46"></a>
  <br>
  <sub>Free. Upload the zip in WordPress, and later updates arrive on the Plugins screen.</sub>
  <br><br>
  <a href="https://hackrepair.github.io/thrg-site-inspector/" title="The Site Inspector project page">Visit the Site Inspector page</a>
</p>

## What it does

**Tools → Site Inspector** checks:

- Updates waiting for WordPress, plugins and themes (from WordPress's own stored update data)
- Administrator accounts, with recent and unusual ones flagged
- PHP files in uploads (tiny placeholder `index.php` files are ignored)
- Backups, `error_log` and debug logs, and leftover `wp-config.php` copies in the web folder
- Must-use plugins and drop-ins
- Unused plugins and themes
- PHP version support and missing PHP security fixes, security keys and salts, the built-in code editor, and PHP error display
- Registration privileges, HTTPS settings, and `wp-config.php` permissions

Each finding says why it matters, what was observed, and what to do next, with the limits of the check one click away. Passing checks are collapsed, and most results link to the admin page where you can act on them.

**Report history.** When history is enabled, inspections are saved on your own site (up to 25 reports within a 600,000-byte storage limit), with what is new and what is resolved since the previous report. Reopen, delete, clear or pause history at any time.

**Share a report.** Copy it as plain text, download it, or print it (usernames are masked on paper), or email a plain-language "state of security" briefing to some or all of the site's administrators, with a subject line that says where things stand.

**Monthly security email.** Off until you turn it on. On a day you choose, Site Inspector checks the site, saves the report and emails the statement to all administrators or the ones you pick, every month or only when something needs attention. Subjects say `Monthly Security Check`.

**Support.** A Support container at the bottom of the screen links to the buy-Jim-a-coffee page and to GitHub Discussions.

**Settings → Site Health Controls** keeps the original feature: switch off the WordPress Site Health menu item, screen redirect, dashboard widget, background tests, weekly cron check, REST routes and AJAX actions, with administrator and user ID exemptions. It also has an opt-in *Delete all plugin data* setting for uninstall.

## Email a report to administrators

1. Open a saved report. In **Send this report to your designer or developer**, choose **Email to administrators**.
2. Review **All administrators** and the individual recipients. The first 25 administrator accounts by display name are considered, and only those with valid email addresses are shown; available recipients are selected by default. Deselect anyone who should not receive it.
3. Optionally add a personal note (up to 1,000 characters), review **Subject**, and choose **Send email**. A **Sending…** label indicates the request is underway.

Subjects use `[domain] [Site Inspector] Security Check: …`, for example `[example.com] [Site Inspector] Security Check: 2 issues need attention (1 new since last check)`. The status and change counts remain in the subject. Both subject preview and send identify the current home URL domain, including subdomains and explicit local ports; they omit the scheme and path and fall back to the site URL if needed. Existing saved reports are not rewritten.

The branded HTML email fills the available width and includes a plain-text alternative. It has a personal greeting, optional note, report findings and next steps, status counts, comparisons with the previous retained report, and a link to open the saved report (administrator sign-in required). Usernames are partly masked. Evidence is condensed: up to three evidence lines and eight table rows per finding, with the full saved report linked for detail. Each recipient gets a separate message; Reply-To uses the sender's valid email address.

Sending requires `manage_options`, a valid nonce, a retained report, and selected current administrator accounts. Emailing a newly generated report requires history to be enabled and that report saved. After at least one message is accepted, the sender must wait one minute before another send. Feedback can report full or partial failure; acceptance for sending does not guarantee inbox delivery. WordPress uses the site's configured mail transport; SMTP configuration may be needed. Local Mailpit capture verifies local receipt, not external delivery.

## Monitor and report site changes

Monitoring is off by default. Under Tools → Site Inspector, turn on **Monitor this site daily** to record the current administrators, plugins and themes. Each daily check lists additions since the previous inventory, including inactive plugins and themes. You can email selected administrators immediately when WordPress grants administrator access, send a daily email when additions appear, or just review changes in the dashboard. For timing, coverage and examples, see the [monitoring guide](docs/site-change-monitoring.md).

## Monthly security email

Under **Tools → Site Inspector**, the **Monthly security email** panel sits right below the share section. Tick **Email a security statement every month**, choose a day from 1 to 28 (about 8 am site time), choose **Every month** or **Only when something needs fixing or a second look**, and choose **All administrators** (including any added later) or specific administrators. An optional note goes out every month.

- Off by default; nothing is scheduled until an administrator saves it turned on.
- Each run inspects the site with nobody logged in, saves the report to history ("Run by: Monthly email"), and emails it using the same template as a one-off email, with a "Monthly" subject, an opening line saying what it is, and a footer line saying how to change or stop it. If history is paused, the email still goes out and links to the Site Inspector screen.
- Recipients are checked against the current administrator list at every send. If none of the chosen administrators can be emailed when saving, the setting is saved as off with a warning.
- Emails are signed with the name of the administrator who last saved the settings, and Reply-To is their address (the site name, with no Reply-To, if they are no longer an administrator).
- It uses WordPress's own scheduler, one queued run at a time. On a quiet site or where WP-Cron is turned off, the run can be late; the panel shows the next due time, the last result, and an overdue warning. A server cron job for `wp-cron.php` fixes it.
- Deactivating cancels the queued run (it returns on reactivation). Uninstalling always cancels it, and *Delete all plugin data* also removes its settings.

## PHP security fixes

The PHP version finding uses a built-in list reviewed on November 23, 2026. It includes the October PHP2Shell fixes and the November 19 security releases: PHP 8.2.36, 8.3.37, 8.4.28 and 8.5.13. The latest applicable fixed release is shown first. The support table includes PHP 8.6; branches absent from the table are marked **Unable to verify**.

An upstream build below a listed fix is a **Potential weakness**. An operating-system package is marked **Review**, because a host may have backported fixes without changing the version number. Advisory impact can depend on configuration and enabled extensions. This is a dated patch list, not a complete vulnerability feed or a test of the running server's exploitability. No outside requests are made during inspection, and a warning appears after the list is more than 90 days old. Sources: [PHP changelog](https://www.php.net/ChangeLog-8.php) and [support dates](https://www.php.net/supported-versions.php).

## Privacy and scope

Monitoring stores the inventory and the latest 50 additions locally. Monitoring emails may include the administrator’s login or the name and location of an added plugin or theme. Turning monitoring off clears the inventory and its change history.

The inspection reads local settings and bounded filesystem metadata without applying fixes, making external requests, or reading file contents. Saving report history and changing Site Health Controls do write plugin settings/data. Reports are stored only in your site's database and include usernames and file names. Reports leave the site only when an administrator chooses to export or email them, or turns on the monthly email, and emails can only go to the site's own administrators. Plugin update discovery and package downloads contact GitHub independently of inspections. Those requests expose normal connection information, such as the server IP and HTTP headers; report content is not included in update requests.

It is not a malware scanner, firewall, vulnerability-feed client, or a complete replacement for every WordPress diagnostic. No overall security score is assigned, and "nothing found" applies only to the check described. Multisite registration policy is explicitly unverified.

## Install

Upload the plugin ZIP under **Plugins → Add New → Upload Plugin**, or place the `thrg-site-inspector` folder in `wp-content/plugins/`, then activate it and open **Tools → Site Inspector**.

On Site Inspector and Site Health Controls screens, other notices can be expanded with **Show**. Late notices join the panel automatically. If the panel cannot initialize, notices remain visible.

The plugin lives in the `thrg-site-inspector` folder and starts from `index.php`. Updates come from this repository's [GitHub releases](https://github.com/hackrepair/thrg-site-inspector/releases "All Site Inspector releases and download files").

## Author and support

**[Jim Walker](https://hackrepair.com/)** — The Hack Repair Guy — author and maintainer.

[Questions, ideas and community support](https://github.com/hackrepair/thrg-site-inspector/discussions) · [Report a bug](https://github.com/hackrepair/thrg-site-inspector/issues) · [Security reporting](SECURITY.md) · [Buy Jim a coffee](https://hackrepair.com/donations/buy-jim-a-coffee)

## License and credits

[GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2"). Started from a 2019 plugin by Louis Fico, used under the GPLv2-or-later license; the original attribution is retained.

Update checks use [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") by Jānis Elsts (MIT license), 6.0, including Parsedown 1.8.0, included unmodified. Output from remote release notes remains sanitized before display.

Made by [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair").

## Changelog

### 2.5.0 (preview)

Added optional daily monitoring for new administrators, plugins and themes, with immediate administrator alerts and a daily email when additions are found.

### 2.4.3

- Keep late admin notices recoverable, and leave notices visible if the notice-panel script cannot initialize.
- Update the bundled Plugin Update Checker to unmodified upstream 6.0, including Parsedown 1.8.0; retain remote markup sanitization and release-only ZIP checks.
- Refresh the built-in PHP security-fix list through November 19, 2026, add PHP 8.6 support dates, and mark unknown branches as unverified.
- Remove database-backed email throttle transients and the schedule lock during opt-in uninstall cleanup.
- Build a compact install ZIP with explicit 0644 permissions and no development docs, workflow files or project video; preserve the last good ZIP/checksum pair if rebuilding fails.
- Correct current-release and dependency documentation. Existing settings and saved reports are retained on upgrade.

Older entries are in the **Changelog** section of [`readme.txt`](readme.txt).
