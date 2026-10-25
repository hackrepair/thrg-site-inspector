=== The Hack Repair Guy's Site Inspector ===
Contributors: hackrepair
Tags: security, audit, site health, report, hardening
Requires at least: 6.9.4
Requires PHP: 7.4
Tested up to: 7.1.3
Stable tag: 2.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Support link: https://github.com/hackrepair/thrg-site-inspector/discussions

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next.

== Description ==

The native View Details window includes an Other THRG Plugins guide to five other tools from The Hack Repair Guy.

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those, in a few seconds, and tells you in plain language what it found, why it matters, and what to do about it. The inspection does not apply fixes to your site. Reports are saved locally when history is enabled.

Open **Tools > Site Inspector** and click **Inspect this site**. It checks:

* Updates waiting for WordPress, plugins and themes (from WordPress's own update data)
* Administrator accounts, including recently created ones and accounts with admin power but no Administrator role
* PHP files hiding in the uploads folder (tiny placeholder index.php files are recognized and ignored)
* Backups, error_log and debug logs, and leftover wp-config copies sitting in the web folder
* Must-use plugins and drop-ins, which run on every page but don't appear in the plugin list
* Unused plugins and themes
* PHP version support, security keys and salts, the built-in code editor, and PHP error display
* Registration privileges, HTTPS settings and wp-config.php permissions

**Every finding is easy to act on.** Each one says why it matters, what was observed and what to do next, with the limits of the check one click away. Passing checks are tucked away so the problems stand out, and most results link straight to the admin page where you can fix them.

**Your reports build a history.** When history is enabled, inspections are saved on your own site (up to 25 reports within a 600,000-byte storage limit), so you can reopen an earlier report and see what is new and what you have fixed since the last one. You can pause history, delete a single report or clear it all at any time.

**Hand it to someone else.** Copy your report as plain text for an email or support ticket, download it as a text file, or print it. Usernames are masked on paper.

**Email a "state of security" briefing.** From any report, send a friendly, plain-language summary to some or all of the site's administrators: what needs fixing first, what's worth a second look, what's already in good shape, and what changed since the last check, with a subject line that tells them where things stand before they open it. You can add a personal note, each administrator gets their own copy, and replies come back to you.

Administrator emails use a full-width branded HTML layout with a plain-text alternative. Subjects follow `[example.com] [Site Inspector] Security Check: 2 issues need attention`, retaining the report status and change counts. The configured home URL supplies the domain, preserving subdomains and explicit ports such as `localhost:8081`. The subject preview matches the sent message.

= Privacy =

The inspection reads local settings and bounded filesystem metadata without applying fixes, making external requests, or reading file contents. Saving report history and changing Site Health Controls do write plugin settings/data. Reports are stored only in your site's database and include usernames and file names, so share them only with people you trust. Reports leave the site only when you choose to export or email them, and emails can only go to the site's own administrators, with usernames partly hidden. Optionally, you can have all plugin data deleted when the plugin is deleted (see below).

= What it is not =

Site Inspector is not a malware scanner, a firewall or a vulnerability database. It does not test what the public can reach or prove that a site is clean. "Nothing found" applies only to the check described. If you want a deeper look, [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair") can help.

= Site Health Controls =

Under **Settings > Site Health Controls** you can choose which parts of WordPress Site Health to switch off: the Tools menu item, direct visits to the screen, the dashboard widget, the background tests (including loopback and REST self-checks), the weekly cron check, the wp-site-health/v1 REST routes and the Site Health AJAX actions. All seven are on until you save your own choices; saved choices are always preserved. These options turn off real diagnostics, so uncheck any you want back.

You can exempt administrators or specific user IDs, define THRG_SHC_ALLOW_ADMINS in wp-config.php, or use the thrg_shc_is_exempt and thrg_shc_redirect_url filters. Network admin Site Health is handled on multisite.

The same page has an **Uninstall** option: tick "Delete all plugin data" and deleting the plugin also removes its settings and saved reports. It is off by default, so nothing is lost if you reinstall.

= Credits =

Update checks use [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker "Plugin Update Checker on GitHub") by Jānis Elsts (MIT license), included unmodified.

Started from a 2019 plugin by Louis Fico, used under the [GPLv2-or-later license](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2").

== Installation ==

1. In WordPress, open Plugins > Add New > Upload Plugin and select the plugin ZIP. You can also upload the thrg-site-inspector folder to wp-content/plugins/.
2. Activate The Hack Repair Guy's Site Inspector.
3. Open Tools > Site Inspector and click Inspect this site.
4. To email a saved report, open it and choose Email to administrators under Send this report to your designer or developer. Review recipients and Subject, optionally add a note, then choose Send email.
5. Optional: review Settings > Site Health Controls to choose which Site Health features stay off.

== Support ==

For questions, ideas and community support, visit GitHub Discussions:
https://github.com/hackrepair/thrg-site-inspector/discussions

Report reproducible bugs through Issues:
https://github.com/hackrepair/thrg-site-inspector/issues

Report security concerns privately to Jim Walker at jim@hackrepair.com. Do not include credentials or sensitive site information in public posts.

== Frequently Asked Questions ==

= Does the inspection change anything or contact outside services? =

No. It reads local settings, file names, sizes and dates, and sends no requests. Update information comes from WordPress's own stored update checks. Each report is saved in your site's database, where you can pause, delete or clear the history.

= Where are my reports kept? =

On your own site, in one database entry. Up to 25 reports are kept, newest first, within a 600,000-byte storage limit; older reports may be dropped sooner to stay within that limit. Identical results within five minutes are not saved twice. Nothing is sent to us. A report leaves the site only when you choose to copy, download, print, or email it. Reports include usernames and file names, so treat a copy like any other sensitive document.

= Why does it flag things that might be fine? =

Some findings are marked Review because only you can say whether they are expected, such as a recently created administrator or a must-use plugin your host installed. Every finding explains what the check can and can't tell you, and a site with nothing unusual shows none.

= Is this a malware scanner? =

No. It looks at the conditions that most often let attackers in, not at whether an attack has already happened. A file in uploads or an unfamiliar admin account is a reason to look closer, not proof of a break-in.

= How do I share a report with my developer or designer? =

Open a report and use the Copy report button (or the Copy report link in the left column). You can also download it as a text file or print it. Printed reports hide usernames.

= Can I email a report to the other administrators? =

Yes. Open a saved report, choose Email to administrators under Send this report to your designer or developer, review All administrators and individual recipients, optionally add a personal note, review Subject, then choose Send email. The first 25 administrator accounts by display name are considered; only those with valid email addresses are listed and available recipients are selected by default. You cannot type in arbitrary recipient addresses. The note limit is 1,000 characters.

Each recipient gets a separate full-width branded HTML email with a plain-text alternative, status counts, findings, recommended next steps, comparisons with the previous retained report, and a link back to the saved report. Usernames are partly masked. Email evidence is condensed to at most three lines and eight table rows per finding. Replies use the sender's valid email address.

A retained report, manage_options permission and a valid nonce are required. To email a new report, enable history before running the inspection. After at least one message is accepted, the sender must wait one minute before sending again. Email acceptance does not guarantee delivery. Mail failures may require configuring the site's WordPress mail transport; an SMTP plugin alone cannot guarantee inbox receipt.

= What format do the email subjects use? =

Version 2.2.4 uses `[domain] [Site Inspector] Security Check: …`, for example `[example.com] [Site Inspector] Security Check: 2 issues need attention`. Status and new/resolved counts remain in the subject. The preview and sent message use the current configured home URL domain, preserving subdomains and explicit ports such as localhost:8081 while omitting scheme/path, with site URL as fallback. Saved report history is unchanged.

= Will it disable WordPress background health checks? =

Only if you leave the Site Health controls on. New installs start with all seven Site Health controls switched on, including the tests and the weekly Site Health cron check. Restore any of them under Settings > Site Health Controls. General WordPress updates and other cron jobs are unaffected.

= What happens if someone opens /wp-admin/site-health.php directly? =

While that control is on, WordPress sends them to the main dashboard. Normal WordPress login and permission rules still apply.

= How can I bring Site Health back? =

Uncheck the options you want back under Settings > Site Health Controls and save, or deactivate the plugin.

= Does this protect server information from unauthorized access? =

WordPress normally restricts the Site Health screen to authorized users. This plugin removes that interface for administrators too; it does not add a security boundary.

= Does the plugin need an outside service or GitHub access? =

Plugin update discovery and package downloads contact GitHub independently of inspections ([hackrepair/thrg-site-inspector](https://github.com/hackrepair/thrg-site-inspector/releases "Site Inspector releases and source code on GitHub")). Update requests expose standard connection information such as server IP and HTTP headers; report contents are not included. The plugin has a local readme/artwork fallback for View details when remote information is unavailable.

= What happens to my data if I uninstall? =

By default nothing is deleted, so a reinstall picks up where you left off. To remove everything, tick "Delete all plugin data" under Settings > Site Health Controls before you delete the plugin. Deactivating never deletes data.

== Changelog ==

= 2.3.0 =
* Added a Support container at the bottom of the Tools > Site Inspector screen: a link to buy Jim a cup of coffee and a link to GitHub Discussions for questions, ideas and help.

= 2.2.6 =
* Fixed ZIP file permissions that could prevent the web server from serving inspector.css, leaving the admin page unstyled.
* All packaged files now use 0644 permissions, verified during every build.


= 2.2.5 =
* Updated 2026 THRG heart branding with a gentle two-cycle animation and matching native View Details banner.
* Respects reduced motion and printing; local PNG/CSS assets require no animation library.


= 2.2.4 =
* Show accepted-for-sending and failed email counts below the administrator form, clarifying that acceptance does not confirm delivery.
* Place the admin-screen version beside the Site Inspector heading.
* Use [domain] [Site Inspector] subject tags while retaining the security-check status and change counts. Include explicit local ports and match the on-screen subject preview.
* Expand the HTML report email to the available width while preserving the plain-text alternative and existing branding.


= 2.2.3 =
* Explain temporary update-check failures clearly and move diagnostic errors into Technical details.
* Show a retry time for confirmed GitHub rate limits.

= 2.2.2 =
* Match the compact companion-plugin cards and sidebar layout used by Disable Other Plugin Notices.
* Display the installed version in View Details.

= 2.2.1 =
* Restore bundled updater parser dependencies required by View Details.

= 2.2.0 =
* Added an Other THRG Plugins tab to View Details with companion tools and project links.

= 2.1.0 =
* New: Email to administrators on every saved report. Send a "state of security" briefing to some or all administrators, with a descriptive subject line, an optional personal note and what changed since the last check. Each administrator gets a personal copy, usernames are partly hidden, replies go to you, and sending is limited to once a minute.
* Refined wording across the inspector, the share box and the plain-text report.

= 2.0.0 =
* New name: The Hack Repair Guy's Site Inspector.
* New Tools > Site Inspector: read-only checks for pending updates, administrator accounts, PHP files in uploads, exposed backups and logs, must-use plugins and drop-ins, unused plugins and themes, PHP version support, security keys, code editor, error display, registration, HTTPS and wp-config.php permissions.
* Plain-language findings with what was observed, what to do next and the limits of each check. Passing checks are collapsed, and results link to the related admin pages.
* Report history: the 25 most recent reports are kept on your site, with what is new and what is resolved since the previous one. View, delete, clear or pause history.
* Share a report: copy as plain text, download, or print with usernames masked.
* New Delete all plugin data option under Settings > Site Health Controls. Uninstalling keeps your settings and reports unless it is ticked.
* Site Health Controls and the inspector share a new look. Added a Run inspection link on the Plugins screen and a one-time notice that points to Site Inspector.
* Updated artwork and plugin details.
* Upgrade note: sites that never saved Site Health settings now have all seven options on, including disabling tests, the weekly check, REST routes and AJAX actions. Saved choices are unchanged.
* Installed folder, entry point, settings and updater are unchanged, so updates keep working.
