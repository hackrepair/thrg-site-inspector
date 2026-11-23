# Site change monitoring in 2.5.0

A site owner may recognize a scheduled plugin installation but miss an unexpected administrator account. Monitor and report site changes gives the owner a short list to check against maintenance work. An addition alone cannot establish who authorized it.

Under Tools → Site Inspector, enable **Monitor this site daily** to take the first inventory. Existing entries become the starting point. Once a day, WordPress compares administrator account IDs, installed plugin files (including inactive standard plugins), and theme directories against the previous inventory. The panel shows the latest 50 additions with their detection times. Updates that retain the same file or directory and theme switches stay quiet.

Choose **Email immediately** to receive a message when WordPress grants the administrator role, including promotions. Choose **Email daily** to receive one summary on days the inventory finds additions. Selecting both can report an administrator immediately and again in the daily inventory. Choose current administrators to receive email. Newly promoted users do not automatically become recipients. Each selected administrator receives a separate message.

The daily check uses WP-Cron, so timing depends on site traffic or the host’s scheduler. An addition removed between checks may leave no daily entry. Immediate alerts depend on WordPress role hooks. The inventory covers standard plugins and themes visible to WordPress. This feature does not inspect modified file contents.

Inventory and recent changes stay in site-local WordPress options. Disabling monitoring clears both and cancels the daily check. Enabling it again starts fresh. Monthly inspection emails have their own settings and schedule.

In 2.5.1, Last monitoring email shows the latest attempt’s time, alert type, accepted count and failed count. The site stores one record without recipient addresses or message contents. Acceptance does not confirm delivery. Turning monitoring off clears the record.
