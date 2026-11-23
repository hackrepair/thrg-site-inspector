# Site Inspector 2.5.0 review fixes

Tracking the review items for the 2.5.0 site change monitoring branch (`feature/change-monitoring`). Each item is updated, verified with a targeted test, and recorded here before moving to the next one.

## Checklist

| # | Priority | Item | Status | Verification |
|---|----------|------|--------|--------------|
| 1 | High | Immediate admin alerts (`add_user_role` vs `set_user_role`) | Verified (no double-hook) | Live Zendogs test confirmed WP 6.9+ `WP_User::set_role()` fires `add_user_role` automatically; kept `add_user_role` only to prevent duplicate alerts |
| 2 | High | `tests/uninstall.php` fails after adding `thrg_si_monitor_daily` | Done | `php tests/uninstall.php` passed (6/6) |
| 3 | Medium | `daily()` concurrency lock, stale cache check, and defensive option normalization | Done | `normalize_settings`, `diff`, `append_log`, lock + uninstall cleanup verified |
| 4 | Medium | Monitoring UX, validation, recipient cap, empty-admin state, and 403 status | Done | Notices, orphaned-recipient warning, empty-admin state, and PHPCS verified |
| 5 | Medium | Unit tests for `THRG_SI_Monitor` (`tests/monitor.php`) | Done | `php tests/monitor.php` passed (19/19) |
| 6 | Low | Polish: PHPCS warning, `$_SERVER['REQUEST_METHOD']`, `Unknown` chip guard, email spacing, uninstall copy | Done | Full PHP lint, PHPCS (0 errors, 0 warnings), PHP + Python test suites passed |

## Work log

1. **Immediate admin alerts (`includes/class-thrg-si-monitor.php`):** Live testing on Zendogs (WP 6.9+) confirmed `WP_User::set_role()` already fires `add_user_role` when a new role is granted (both on `wp_insert_user` and promotions) and skips it when the user already has that role. Keeping only `add_user_role` avoids duplicate alerts while covering new admins, promotions, and `add_role()`.
2. **Uninstall test (`tests/uninstall.php`):** Updated expected cleared hooks to include `thrg_si_monitor_daily` and added `thrg_si_monitor`, `thrg_si_inventory`, and `thrg_si_monitor_lock` to the opt-in cleanup checks.
3. **Daily monitoring hardening (`includes/class-thrg-si-monitor.php`, `uninstall.php`):** Added `LOCK` transient (`thrg_si_monitor_lock`) with `try`/`finally` release, cache-invalidated mid-run disable check, and pure helpers (`normalize_settings`, `diff`, `append_log`) to guard against malformed option data.
4. **Monitoring UX and validation (`includes/class-thrg-si-monitor.php`, `includes/view-monitor.php`, `includes/view-inspector.php`):** Replaced hard `wp_die()` validation with inline `thrg_si_done` notices (`monitor-saved`, `monitor-off`, `monitor-none`), returned HTTP 403/405 on unauthorized/non-POST requests, capped recipients at `THRG_SI_Email::MAX_RECIPIENTS`, added an empty-admins note, and warned when saved recipients are no longer valid administrators.
5. **Monitoring unit tests (`tests/monitor.php`):** Added 19 unit tests covering hook registration, normalization, diffing, bounded logging, immediate role alerts, lock handling, daily digests, and mid-run disable.
6. **Polish (`includes/class-thrg-site-inspector.php`, `includes/view-inspector.php`, `includes/class-thrg-si-monitor.php`, `index.php`):** Fixed PHPCS alignment warning, guarded `$_SERVER['REQUEST_METHOD']`, guarded `https-config`/`file-editor` chips against `Unknown` status, added blank-line spacing before the review link in plain-text alert emails, and updated the uninstall settings copy.
