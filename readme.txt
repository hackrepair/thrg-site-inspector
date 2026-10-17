=== The Hack Repair Guy's Site Inspector ===
Contributors: hackrepair
Tags: security, audit, site health, report, hardening
Requires at least: 6.9.4
Requires PHP: 7.4
Tested up to: 7.1.3
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next.

== Description ==

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those, in a few seconds, and tells you in plain language what it found, why it matters, and what to do about it. It never changes anything on your site.

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

**Your reports build a history.** Each inspection is saved on your own site (the 25 most recent), so you can reopen an earlier report and see what is new and what you have fixed since the last one. You can pause history, delete a single report or clear it all at any time.

**Hand it to someone else.** Copy your report as plain text for an email or support ticket, download it as a text file, or print it. Usernames are masked on paper.

**Email a "state of security" briefing.** From any report, send a friendly, plain-language summary to some or all of the site's administrators: what needs fixing first, what's worth a second look, what's already in good shape, and what changed since the last check, with a subject line that tells them where things stand before they open it. You can add a personal note, each administrator gets their own copy, and replies come back to you.

= Privacy =

The inspection is read-only. It makes no changes, sends no network requests and never reads file contents. Reports are stored only in your site's database and include usernames and file names, so share them only with people you trust. No report leaves the site unless you choose to email it, and emails can only go to the site's own administrators, with usernames partly hidden. Optionally, you can have all plugin data deleted when the plugin is deleted (see below).

= What it is not =

Site Inspector is not a malware scanner, a firewall or a vulnerability database. It does not test what the public can reach or prove that a site is clean. "Nothing found" applies only to the check described. If you want a deeper look, [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair") can help.

= Site Health Controls =

Under **Settings > Site Health Controls** you can choose which parts of WordPress Site Health to switch off: the Tools menu item, direct visits to the screen, the dashboard widget, the background tests (including loopback and REST self-checks), the weekly cron check, the wp-site-health/v1 REST routes and the Site Health AJAX actions. All seven are on until you save your own choices; saved choices are always preserved. These options turn off real diagnostics, so uncheck any you want back.

You can exempt administrators or specific user IDs, define THRG_SHC_ALLOW_ADMINS in wp-config.php, or use the thrg_shc_is_exempt and thrg_shc_redirect_url filters. Network admin Site Health is handled on multisite.

The same page has an **Uninstall** option: tick "Delete all plugin data" and deleting the plugin also removes its settings and saved reports. It is off by default, so nothing is lost if you reinstall.

= Credits =

Started from a 2019 plugin by Louis Fico, used under the [GPLv2-or-later license](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2").

== Installation ==

1. In WordPress, open Plugins > Add New > Upload Plugin and select the plugin ZIP. You can also upload the thrg-site-inspector folder to wp-content/plugins/.
2. Activate The Hack Repair Guy's Site Inspector.
3. Open Tools > Site Inspector and click Inspect this site.
4. Optional: review Settings > Site Health Controls to choose which Site Health features stay off.

== Frequently Asked Questions ==

= Does the inspection change anything or contact outside services? =

No. It reads local settings, file names, sizes and dates, and sends no requests. Update information comes from WordPress's own stored update checks. Each report is saved in your site's database, where you can pause, delete or clear the history.

= Where are my reports kept? =

On your own site, in one database entry. The 25 most recent reports are kept, newest first, and older ones are dropped automatically. Nothing is sent to us. A report only leaves the site if you email it to your administrators. Reports include usernames and file names, so treat a copy like any other sensitive document.

= Why does it flag things that might be fine? =

Some findings are marked Review because only you can say whether they are expected, such as a recently created administrator or a must-use plugin your host installed. Every finding explains what the check can and can't tell you, and a site with nothing unusual shows none.

= Is this a malware scanner? =

No. It looks at the conditions that most often let attackers in, not at whether an attack has already happened. A file in uploads or an unfamiliar admin account is a reason to look closer, not proof of a break-in.

= How do I share a report with my developer or designer? =

Open a report and use the Copy report button (or the Copy report link in the left column). You can also download it as a text file or print it. Printed reports hide usernames.

= Can I email a report to the other administrators? =

Yes. Open any saved report, choose Email to administrators under the share options, and pick who should get it. Only administrator accounts on the site can be chosen; you can't type in other addresses. The email uses your site's normal WordPress mail, so if your site can't send email (common on some hosts), an SMTP plugin will fix that.

= Will it disable WordPress background health checks? =

Only if you leave the Site Health controls on. New installs start with all seven Site Health controls switched on, including the tests and the weekly Site Health cron check. Restore any of them under Settings > Site Health Controls. General WordPress updates and other cron jobs are unaffected.

= What happens if someone opens /wp-admin/site-health.php directly? =

While that control is on, WordPress sends them to the main dashboard. Normal WordPress login and permission rules still apply.

= How can I bring Site Health back? =

Uncheck the options you want back under Settings > Site Health Controls and save, or deactivate the plugin.

= Does this protect server information from unauthorized access? =

WordPress normally restricts the Site Health screen to authorized users. This plugin removes that interface for administrators too; it does not add a security boundary.

= Does the plugin need an outside service or GitHub access? =

The only outside request is WordPress's normal update check against this plugin's GitHub releases ([hackrepair/thrg-site-inspector](https://github.com/hackrepair/thrg-site-inspector/releases "Site Inspector releases and source code on GitHub")). Apart from report emails you choose to send, nothing else is sent anywhere, and the View details window uses the readme and artwork installed with the plugin.

= What happens to my data if I uninstall? =

By default nothing is deleted, so a reinstall picks up where you left off. To remove everything, tick "Delete all plugin data" under Settings > Site Health Controls before you delete the plugin. Deactivating never deletes data.

== Changelog ==

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
