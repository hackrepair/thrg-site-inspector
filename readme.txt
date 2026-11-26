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

Most hacked WordPress sites were open to the same handful of things: out-of-date software, an admin account nobody remembers, a stray PHP file in uploads, a backup left in the web folder.

Site Inspector looks for exactly those in a few seconds, then tells you in plain language what it found, why it matters and what to do about it. The inspection doesn't change your site. If you keep history on, reports are saved on your own site.

Open **Tools > Site Inspector** and click **Inspect this site**. It checks:

* Updates waiting for WordPress, plugins and themes (from WordPress's own update data)
* Administrator accounts, including recently created ones and accounts with admin power but no Administrator role
* PHP files hiding in the uploads folder (it recognizes and skips the tiny placeholder index.php files)
* Backups, error_log and debug logs, and leftover wp-config copies sitting in the web folder
* Must-use plugins and drop-ins, which run on every page but don't show in the plugin list
* Plugins and themes you aren't using
* Whether your PHP version is still supported, and whether a known PHP security fix is missing
* Security keys and salts, the built-in code editor and PHP error display
* Registration privileges, HTTPS settings and wp-config.php permissions

Each finding tells you why it matters, what Site Inspector saw and what to do next, with the limits of the check one click away. Passing checks are tucked out of the way so the problems stand out, and most results link straight to the admin page where you fix them.

With history on, Site Inspector saves up to 25 reports on your site, within a 600 KB limit. Reopen an earlier report to see what's new and what you've fixed since the last one. You can pause history, delete a single report or clear them all at any time.

To hand a report to someone else, copy it as plain text for an email or support ticket, download it as a text file, or print it. Printed copies mask usernames.

From any report, you can also email a "state of security" briefing to some or all of your site's administrators. It covers what needs fixing first, what's worth a second look, what's already in good shape and what changed since the last check. The subject line tells them where things stand before they open it. Add a personal note if you like. Each administrator gets their own copy, and replies come back to you.

Change monitoring is off by default. Under **Tools > Site Inspector**, turn on **Monitor this site daily** and it records your current administrators, plugins and themes. Each daily check lists anything added since the last inventory, inactive plugins and themes included. Then you choose: email selected administrators as soon as WordPress grants someone administrator access, send a daily email when additions show up, or just review the changes in your dashboard.

The monthly security statement is off by default too. Turn it on and pick a day, and Site Inspector checks the site and sends the statement to all administrators or the ones you choose. Send it every month, or only when something needs fixing or a second look.

Emails use a full-width branded layout with a plain-text version. The subject starts with your site's address, subdomain and port included, and carries the report status and change counts, like `[example.com] [Site Inspector] Security Check: 2 issues need attention`. The subject you preview is the subject that's sent.

On the Site Inspector and Site Health Controls screens, other notices are folded away. Click **Show** to see them. Notices that load late are included, and if the panel can't load, notices stay visible.

= Privacy =

The inspection reads your site's settings and a limited set of file and folder details. It doesn't open file contents, contact outside services or apply fixes. Saving report history and changing Site Health Controls do save plugin settings and data.

Reports are stored only in your site's database. They include usernames and file names, so share them only with people you trust. A report leaves your site only when you export or email it, or when you've turned on the monthly email. Emails can only go to the site's own administrators, with usernames partly hidden.

Monitoring keeps its inventory, the latest 50 additions and the status of its last email on your site. A monitoring email may include an administrator's login, or the name and location of a newly added plugin or theme. Turning monitoring off clears the inventory, its change history and the email status.

You can also have deleting the plugin delete all of its data (see Site Health Controls below).

= What it isn't =

Site Inspector isn't a malware scanner, a firewall or a vulnerability database. It doesn't test what the public can reach, and it can't prove a site is clean. "Nothing found" applies only to the check described. If you want a deeper look, [The Hack Repair Guy](https://hackrepair.com/ "The Hack Repair Guy: WordPress security and hack repair") can help.

= Site Health Controls =

Under **Settings > Site Health Controls** you choose which parts of WordPress Site Health to switch off:

* The Tools menu item
* Direct visits to the Site Health screen
* The dashboard widget
* The background tests, including loopback and REST self-checks
* The weekly cron check
* The wp-site-health/v1 REST routes
* The Site Health AJAX actions

Nothing is switched off until you tick it and save, so Site Health keeps working until you decide otherwise. Each of these turns off real diagnostics, so only tick the ones you need.

You can exempt administrators or specific user IDs, define `THRG_SHC_ALLOW_ADMINS` in wp-config.php, or use the `thrg_shc_is_exempt` and `thrg_shc_redirect_url` filters. On multisite, it covers network admin Site Health too.

The same page has an **Uninstall** option. Tick "Delete all plugin data" and deleting the plugin also removes its settings and saved reports. It's off by default, so nothing is lost if you reinstall.

== External services ==

Site Inspector doesn't contact any outside service. Inspections, report history, change monitoring and the monthly email all run on your own site. Emails go only to the site's own administrators, through your site's normal WordPress mail setup. The PHP support dates and security fixes it checks against are built into the plugin and need no request.

Site Inspector has no account, license server, analytics or advertising requests.

== Installation ==

1. In WordPress, open Plugins > Add New Plugin, search for "The Hack Repair Guy's Site Inspector" and choose Install Now. You can also upload the thrg-site-inspector folder to wp-content/plugins/.
2. Activate The Hack Repair Guy's Site Inspector.
3. Open Tools > Site Inspector and click Inspect this site.
4. To email a saved report, open it and choose Email to administrators under Send this report to your designer or developer. Review recipients and Subject, optionally add a note, then choose Send email.
5. Optional: under Settings > Site Health Controls, choose any Site Health features you want switched off. Nothing is switched off until you save a choice there.

== Support ==

Hi, I'm Jim, the author. Questions, ideas, or stuck on something? I'd love to hear from you and I'm happy to help. Visit the plugin's support forum on WordPress.org:
https://wordpress.org/support/plugin/thrg-site-inspector/

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

== Screenshots ==

1. A report: what needs attention first, what to review, and what was found clean, with a plain-language next step for each finding.
2. Share a report: copy it, download it, print it, or email a "state of security" summary to the site's administrators.
3. The optional monthly security email: pick the day, when to send, and who receives it.

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
