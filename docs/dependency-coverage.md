# Repository protection and dependency coverage

## Verify

With an authenticated GitHub CLI account that can inspect repository settings, run:

```bash
python3 scripts/verify-repository-protection.py
```

This read-only check requires secret scanning, push protection and Dependabot alerts to be enabled, and requires GitHub's dependency graph to contain every package/version or Actions ref detected by `dependency-inventory.py`. An empty graph cannot pass when dependencies are expected. API failures and missing entries fail the check. Allow indexing to finish after submission before checking again.

To inspect the local inventory without contacting GitHub:

```bash
python3 scripts/dependency-inventory.py --output /tmp/thrg-dependencies.json
```

The Dependency inventory workflow submits snapshots on pushes to main or a manual dispatch. It does not run for pull requests. The checkout action is pinned to a verified commit and does not persist credentials. Its job-scoped contents-write permission is required by GitHub for dependency submission; it is not an external app grant or a credential stored in the repository.

## Coverage

- Where bundled, Plugin Update Checker is identified from its source header and Parsedown from its declared version. Both use verified Composer package identities so GitHub can match advisory records. These version declarations can identify an upstream baseline even if the bundled copy is modified; advisory findings require source review rather than automatic dismissal.
- The modified, unversioned `PucReadmeParser.php`, where present, is tracked as a manual-review item with its SHA-256. No package identity or version is invented for it.
- External GitHub Actions refs are inventoried from committed workflow YAML. Existing floating version tags are declared refs, not immutable artifact identities. The new inventory workflow itself pins its checkout action.
- Plugin Archiver does not bundle this updater. Its inventory covers its workflow dependencies; this does not imply zero security risk in its PHP code.

## Parsedown advisories (assessed October 26, 2026)

Dependabot reports two open alerts on the bundled Parsedown 1.6.0: GHSA-qgpv-86r3-87fh (CVE-2018-1000162, XSS, fixed in 1.7.0) and GHSA-62m3-fc7f-jpp8 (CVE-2019-10905, class-name injection, fixed in 1.7.2). Plugin Update Checker 5.7, the newest upstream release, still bundles 1.6.0, so there is no newer upstream copy to swap in, and the vendored folder stays unmodified by policy.

Exposure: Parsedown only renders Markdown from this plugin's own GitHub release notes or readme, and shows the result in the update details dialog to administrators. Nothing a site visitor, report or inspection supplies reaches it. Abusing it would need write access to the plugin's GitHub releases, which would also let an attacker replace the update package, so the advisories add little beyond that existing trust.

Mitigation (source, after 2.3.1): `THRG_SI_Updater::clean_info()` runs on the update checker's `request_info_result` filter and passes every text section through `wp_kses` with WordPress's post allowlist minus `class` attributes. Checked on WordPress 6.9.4 / PHP 7.4.33 with hostile Markdown: `<script>`, event-handler attributes and `javascript:` links are removed and class attributes dropped, while bold, links and code survive. `tests/updater-sanitize.php` covers the filter. The alerts remain open because GitHub matches on the declared library version; dismiss them as "vulnerable code is not used" only after this ships, or when upstream bundles a fixed Parsedown (then replace the whole folder).

## Limits and maintenance

This is an inventory of identified bundled libraries and Actions refs, not a complete resolved dependency lock or a security audit. WordPress and PHP on client sites, transitive dependencies of Actions, and development tools downloaded by commands such as `npx @wordpress/env@10` are not fully resolved here. No fake Composer/npm manifest is added, and no library is automatically upgraded or merged. Updating a bundled library requires the existing review, tests and release process.

The workflow reads current source declarations on each run and fails if an expected bundled parser version cannot be read. Review the inventory rules when adding/removing third-party code, renaming vendor files or changing build tooling. Do not interpret zero alerts as a clean bill of security. The published install ZIPs exclude this workflow, scripts and documentation. No plugin release or WordPress.org SVN change is made by enabling repository protections.
