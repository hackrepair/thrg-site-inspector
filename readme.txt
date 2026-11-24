=== The Hack Repair Guy's Site Inspector ===
Contributors: hackrepair, tvcnet
Donate link: https://hackrepair.com/donations/buy-jim-a-coffee
Tags: security, audit, site health, report, hardening
Requires at least: 6.9
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 2.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Your friendly WordPress security inspector. Keeps a history of every report, so you can see what's new, what's fixed, and what's next.

== Description ==

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder. Site Inspector looks for exactly those, in a few seconds, and tells you in plain language what it found, why it matters, and what to do about it. The inspection does not apply fixes to your site. Reports are saved locally when history is enabled.

Open **Tools > Site Inspector** and click **Inspect this site**. It checks:

* Updates waiting for WordPress, plugins and themes (from WordPress's own update data)
* Administrator accounts, including recently created ones and accounts with admin power but no Administrator role
* PHP files hiding in the uploads folder (tiny placeholder index.php files are recognized and ignored)
* Backups, error_log and debug logs, and leftover wp-config copies sitting in the web folder
* Must-use plugins and drop-ins, which run on every page but don't appear in the plugin list
* Unused plugins and themes
* PHP version support, and whether a known PHP security fix is missing, plus security keys and salts, the built-in code editor, and PHP error display
* Registration privileges, HTTPS settings and wp-config.php permissions

**Every finding is easy to act on.** Each one says why it matters, what was observed and what to do next, with the limits of the check one click away. Passing checks are tucked away so the problems stand out, and most results link straight to the admin page where you can fix them.

**Your reports build a history.** When history is enabled, inspections are saved on your own site (up to 25 reports within a 600,000-byte storage limit), so you can reopen an earlier report and see what is new and what you have fixed since the last one. You can pause history, delete a single report or clear it all at any time.

**Hand it to someone else.** Copy your report as plain text for an email or support ticket, download it as a text file, or print it. Usernames are masked on paper.

**Email a "state of security" briefing.** From any report, send a friendly, plain-language summary to some or all of the site's administrators: what needs fixing first, what's worth a second look, what's already in good shape, and what changed since the last check, with a subject line that tells them where things stand before they open it. You can add a personal note, each administrator gets their own copy, and replies come back to you.

**Monitor and report site changes.** Monitoring is off by default. Under Tools → Site Inspector, turn on **Monitor this site daily** to record the current administrators, plugins and themes. Each daily check lists additions since the previous inventory, including inactive plugins and themes. You can email selected administrators immediately when WordPress grants administrator access, send a daily email when additions appear, or just review changes in the dashboard.

**Get a monthly security statement.** Turn on the monthly email to have Site Inspector check the site on a day you choose and send the statement to all administrators or the ones you pick. It is off until you turn it on. Send it every month, or only when something needs fixing or a second look.

Administrator emails use a full-width branded HTML layout with a plain-text alternative. Subjects follow `[example.com] [Site Inspector] Security Check: 2 issues need attention`, retaining the report status and change counts. The configured home URL supplies the domain, preserving subdomains and explicit ports such as `localhost:8081`. The subject preview matches the sent message.

= Privacy =

Monitoring stores the inventory, the latest 50 additions, and the latest monitoring email attempt status locally. Monitoring emails may include the administrator’s login or the name and location of an added plugin or theme. Turning monitoring off clears the inventory, its change history, and the email status.

The inspection reads local settings and bounded filesystem metadata without applying fixes, making external requests, or reading file contents. Saving report history and changing Site Health Controls do write plugin settings/data. Reports are stored only in your site's database and include usernames and file names, so share them only with people you trust. Reports leave the site only when you choose to export or email them, or when you turn on the monthly email, and emails can only go to the site's own administrators, with usernames partly hidden. Optionally, you can have all plugin data deleted when the plugin is deleted (see below).

= What it is not =

Site Inspector is not a malware scanner, a firewall or a vulnerability database. It does not test what the public can reach or prove that a site is clean. "Nothing found" applies only to the check described. If you want a deeper look, [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair") can help.

= Site Health Controls =

Under **Settings > Site Health Controls** you can choose which parts of WordPress Site Health to switch off: the Tools menu item, direct visits to the screen, the dashboard widget, the background tests (including loopback and REST self-checks), the weekly cron check, the wp-site-health/v1 REST routes and the Site Health AJAX actions. All seven are off until you tick them and save, so Site Health keeps working until you decide otherwise. These options turn off real diagnostics, so switch on only the ones you need.

You can exempt administrators or specific user IDs, define THRG_SHC_ALLOW_ADMINS in wp-config.php, or use the thrg_shc_is_exempt and thrg_shc_redirect_url filters. Network admin Site Health is handled on multisite.

The same page has an **Uninstall** option: tick "Delete all plugin data" and deleting the plugin also removes its settings and saved reports. It is off by default, so nothing is lost if you reinstall.

= Credits =

Started from a 2019 plugin by Louis Fico, used under the [GPLv2-or-later license](https://www.gnu.org/licenses/gpl-2.0.html "Read the GNU General Public License, version 2").

On Site Inspector and Site Health Controls screens, choose Show to expand other notices. Late notices are included automatically, and notices stay visible if the panel cannot initialize.

== Installation ==

1. In WordPress, open Plugins > Add New Plugin, search for "Site Inspector Hack Repair Guy" and click Install Now. You can also upload the thrg-site-inspector folder to wp-content/plugins/.
2. Activate The Hack Repair Guy's Site Inspector.
3. Open Tools > Site Inspector and click Inspect this site.
4. To email a saved report, open it and choose Email to administrators under Send this report to your designer or developer. Review recipients and Subject, optionally add a note, then choose Send email.
5. Optional: under Settings > Site Health Controls, choose any Site Health features you want switched off. Nothing is switched off until you save a choice there.

== Frequently Asked Questions ==

= Does the inspection change anything or contact outside services? =

No. It reads local settings, file names, sizes and dates, and sends no requests. Update information comes from WordPress's own stored update checks. Each report is saved in your site's database, where you can pause, delete or clear the history.

= Where are my reports kept? =

On your own site, in one database entry. Up to 25 reports are kept, newest first, within a 600,000-byte storage limit; older reports may be dropped sooner to stay within that limit. Identical results within five minutes are not saved twice. Nothing is sent to us. A report leaves the site only when you choose to copy, download, print, or email it, or when the monthly email you turned on goes out. Reports include usernames and file names, so treat a copy like any other sensitive document.

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

= Can reports be emailed automatically every month? =

Yes. Under Tools > Site Inspector, open **Monthly security email**, tick **Email a security statement every month**, choose a day (1 to 28), choose whether to send every month or only when something needs fixing or a second look, and choose all administrators or specific ones. You can add a note that goes out every month. Then choose **Save monthly email**.

It is off by default. Each run inspects the site, saves the report to your history, and emails it at about 8 am site time. Emails are signed with the name of the administrator who last saved the settings, and replies go to them. Recipients are checked again before every send, so someone who is no longer an administrator is dropped. WordPress runs scheduled jobs when someone visits the site, so on a quiet site the email can arrive late; the screen warns when a run is overdue, and a server cron job for wp-cron.php fixes it. Deactivating the plugin cancels the next run; it comes back when the plugin is reactivated.

= Why does the PHP version check mention a security fix? =

Being on a supported PHP branch is not the same as being patched. Site Inspector has a built-in list of recent PHP security releases (reviewed November 23, 2026, covering the October PHP2Shell fixes and November 19 security releases). If the site's PHP version is older than the release with the fix, the check says so, with the date and the advisory numbers. Plain PHP builds are marked as a potential weakness. Operating-system packages (Debian, Ubuntu, Red Hat, CloudLinux) often carry the fix under the old version number, so those are marked for review instead. The latest applicable fixed release is selected first. PHP 8.6 support dates are included; unknown branches are marked Unable to verify. Impact depends on configuration and extensions. The check makes no outside requests, so the list is only as current as the plugin; it says when the list is more than 90 days old.

= What format do the email subjects use? =

Subjects use `[domain] [Site Inspector] Security Check: …`, for example `[example.com] [Site Inspector] Security Check: 2 issues need attention`. The monthly email says `Monthly Security Check`. Status and new/resolved counts remain in the subject. The preview and sent message use the current configured home URL domain, preserving subdomains and explicit ports such as localhost:8081 while omitting scheme/path, with site URL as fallback. Saved report history is unchanged.

= Will it disable WordPress background health checks? =

Not unless you ask it to. All seven Site Health controls start switched off, so the tests and the weekly Site Health cron check keep running. Switch any of them on under Settings > Site Health Controls. General WordPress updates and other cron jobs are unaffected.

= What happens if someone opens /wp-admin/site-health.php directly? =

While that control is on, WordPress sends them to the main dashboard. Normal WordPress login and permission rules still apply.

= How can I bring Site Health back? =

Uncheck the options you want back under Settings > Site Health Controls and save, or deactivate the plugin.

= Does this protect server information from unauthorized access? =

WordPress normally restricts the Site Health screen to authorized users. If you switch the controls on, the interface is removed for administrators too unless you exempt them; it does not add a security boundary.

= Does the plugin need an outside service or an account? =

No. Site Inspector makes no outside requests, needs no account or license key, and every feature works out of the box. Emails go out through your site's own WordPress mail setup, only to the site's administrators. Updates come through WordPress.org like any other plugin.

= What happens to my data if I uninstall? =

By default nothing is deleted, so a reinstall picks up where you left off. Uninstall cancels the monthly email and the daily monitoring check. Opt-in cleanup also removes the schedule and monitoring locks, monitoring email status, and database-backed email throttle transients; any external-cache throttle entries expire after one minute. To remove saved plugin data, tick "Delete all plugin data" under Settings > Site Health Controls before you delete the plugin. Deactivating never deletes data.

= Where can I get help? =

For questions and help, use the plugin's support forum on WordPress.org. Ideas and discussion are also welcome on GitHub Discussions:
https://github.com/hackrepair/thrg-site-inspector/discussions

Report reproducible bugs through Issues:
https://github.com/hackrepair/thrg-site-inspector/issues

Report security concerns privately to Jim Walker at jim@hackrepair.com. Do not include credentials or sensitive site information in public posts.

== Changelog ==

= 2.6.0 =
* The welcome notice appears once, on the Plugins screen and Site Health Controls, and retires after your first visit to Tools > Site Inspector.
* First release in the WordPress.org plugin directory.
* Site Health Controls start switched off; nothing in Site Health changes until you choose it under Settings > Site Health Controls.
* The share-box and monthly-email scripts now load from a script file instead of inline code.
* Requires WordPress 6.9 or newer.

= 2.5.2 =

* The monitoring panel shows the next scheduled check in site time and warns when the check is overdue or missing.

* Monthly email shows accepted and failed counts and reloads settings after inspection, before sending. Daily and monthly settings share recipient-ID validation.

= 2.5.1 =

* Monitoring now shows the time, alert type, and accepted/failed counts for the latest email attempt. Acceptance does not confirm delivery. Turning monitoring off clears this status. Copy feedback handles failed clipboard attempts, printed reports omit the email and monitoring settings panels, and mobile headings use the intended size. Removed the unused legacy logo and corrected updater type comments.

= 2.5.0 =

* Added optional daily monitoring for new administrators, plugins and themes, with immediate administrator alerts and a daily email when additions are found.

= Earlier releases =
* Versions 2.0.0 to 2.4.3 were published on GitHub: https://github.com/hackrepair/thrg-site-inspector/releases
