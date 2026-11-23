# Site Inspector 2.4.3 release verification

Date: November 23, 2026. Distribution: GitHub. Upgrade baseline: `v2.4.2` (`bb5d98054ddc680570d431f1aa6b406c98326a37`). The release is published and the Zendogs upgrade is verified below.

## Changes and review disposition

The full baseline review found no confirmed exploitable vulnerability in its stated scope. The release resolves the late-notice recovery and failed-rebuild defects, updates the complete upstream dependency bundle, refreshes PHP patch/support data, removes database-backed transient leftovers during opt-in uninstall, and corrects current documentation. Prior dated review records describe their historical versions.

All first-party runtime files were covered in the baseline review. The release delta is reviewed separately, including its new prepared uninstall query, notice observer/fallback, staged builder and dependency integration. Upstream v6.0 files are preserved byte for byte; vendor internals are not claimed to have received a complete independent audit.

## Validation before publication

- Shared project PHPCS and PHPCompatibilityWP: 0 errors, 0 warnings; minimum PHP requirement stays 7.4.
- All eleven PHP test files, two build/package Python tests and the JavaScript notice regression passed. PHP syntax checks cover all shipped PHP files.
- The release-only updater regression rejects absent, wrongly named, mismatched and prefixed ZIPs, malformed tags and unavailable release information; source tag/branch archives are never selected. Unsupported APIs disable the checker.
- In disposable WordPress 7.1 / PHP 8.2.33, 35 handler/workflow assertions passed: authorization, nonces, report access, recipient demotion, escaping, inspection save and scheduled-callback behavior. Mail was blocked before transport.
- Actual renderer plus WordPress sanitizer passed with both the new 1.8.0 parser and an older 1.6.0 parser supplied first by a sibling updater. Both v5p7 and v6p0 checker APIs loaded together without collision. The bundled inventory does not promise which global parser other plugins load.
- Opt-in uninstall removed the schedule lock and database-backed mail throttles, settings/history and the queued event. Persistent external-cache mail throttle entries expire after one minute.
- Missing input, excluded required content and a failed checksum replacement preserve the previous ZIP/checksum pair. No old release assets were overwritten.
- The extracted final ZIP received Plugin Check: 65 errors and 51 warnings. Three first-party errors are intentional custom-updater detections for this GitHub-distributed build. The other 62 errors are in the unmodified upstream library. First-party warnings cover translation-loader guidance, typed/allowlisted recipient reads, server request-method comparisons and intentional one-time prepared uninstall cleanup; all were traced. These are not represented as a clean WordPress.org submission result.
- Six security-only sniff leads on typed recipient expressions and literal request-method comparisons remain documented false positives after tracing and real authorization tests. The project ruleset passes. One vendor trailing-whitespace change is unmodified upstream content; first-party diff whitespace checks pass.

The package excludes docs/video, tests, scripts, workflows and review artifacts. All ZIP members use Unix regular-file mode 0644 and one `thrg-site-inspector/` root. The version/header/readme are 2.4.3; the standard WordPress Tested up to field is 7.1. The ZIP and SHA-256 are recorded with final publication evidence.

## Limits

No production security clearance, external email-delivery claim, full vendor audit, minimum-PHP runtime matrix or multisite runtime sign-off is made. The earlier broad review and this release validation remain separate from repository-policy/secret-scanning settings. No separate automatic security service was run. Re-run the dependency-coverage verifier after the main-branch workflow indexes 6.0/1.8.0.

## Publication and installed-package verification

Published: https://github.com/hackrepair/thrg-site-inspector/releases/tag/v2.4.3 at 2026-11-23 18:58:30 UTC. Tag `v2.4.3` points to reviewed commit `105060981cd5cecbdcf49bad0fff96184d539126`.

ZIP: `thrg-site-inspector.2.4.3.zip`, 653,936 bytes, 144 files. SHA-256: `a11fa5e0efe36b74d8506ee79152513e2344e29dbd67d76b7951f2ccbe2f097e`. The matching checksum sidecar is attached. Both the GitHub asset digest and a downloaded ZIP match the tested artifact. Older releases were retained.

The existing registered updater on Zendogs (`localhost:8081`) detected 2.4.3 and offered this exact GitHub release asset. The standard WordPress plugin updater installed it over 2.4.2, retained activation, settings, history pause state, monthly-email configuration and all seven saved reports. Every installed file matches the published ZIP, every installed file is 0644, and stylesheet/script responses are HTTP 200 with matching hashes. Browser verification confirmed the styled 2.4.3 screen and native View Details version, banner, requirements and companion tab. No inspection or email was triggered on Zendogs.

An initial extra diagnostic helper failed while constructing a second checker; it was discarded and the already-registered updater was used for discovery. The subsequent normal discovery and upgrade passed. No helper is installed in the plugin; temporary diagnostic scripts were removed. A pre-upgrade folder backup remains at `/tmp/thrg-si-before-2.4.3-2026-11-23.tgz` inside the Zendogs container.

The final second pass reviewed 16 selected files, skipped 0 and found no unresolved defect. Of 53 excluded paths, 47 are upstream-library changes separately verified for byte provenance/integration and 6 are Markdown records separately checked. The baseline full review and release-delta evidence remain under the outer project directory, outside the install ZIP.

Dependency inventory workflow passed: https://github.com/hackrepair/thrg-site-inspector/actions/runs/45243963514. Pages deployment passed: https://github.com/hackrepair/thrg-site-inspector/actions/runs/45243961712. Read-only repository-protection, indexed 6.0/1.8.0 dependency coverage and private-reporting policy checks passed after publication. Open Dependabot alerts returned zero at this check; that is an observation, not a security guarantee.

Real-browser lab checks also covered late-notice recovery and desktop/390px native details without horizontal overflow. The disposable containers/network were removed after the tests.
