# Release Checklist

Use this checklist for every tagged release.

## 1) Versioning

- [ ] Update `Version:` in `hkdev-shop-elements.php`
- [ ] Update `HKDEV_ELEMENTS_VERSION` in `hkdev-shop-elements.php`
- [ ] Verify changelog/release notes draft matches actual changes

## 2) Migration Safety Gate

- [ ] Run migration audit (normal): `php scripts/migration-audit.php`
- [ ] Run migration audit (strict): `php scripts/migration-audit.php --strict`
- [ ] If PHP CLI is unavailable, run through wrappers:
  - Bash: `./scripts/audit-migration.sh --strict`
  - PowerShell: `.\scripts\audit-migration.ps1 -Strict`

## 3) Functional Confidence

- [ ] Smoke bootstrap (if local PHP CLI available): `php scripts/boot-smoke-test.php`
- [ ] Verify plugin activates in WP Admin without fatal
- [ ] Verify Elementor panel loads and HKDEV category/widgets appear
- [ ] Quick frontend check: shop/cart/checkout/account pages render

## 4) Release Build & Publish

- [ ] Commit and push all release changes
- [ ] Create and push tag: `vX.Y.Z`
- [ ] Required CODEOWNERS reviews are approved (if branch protection enforces it)
- [ ] Confirm release workflow summary shows governance reminder
- [ ] Confirm GitHub Action `Release Plugin Zip` passes
- [ ] Confirm release asset `dist/hkdev-shop-elements.zip` is attached

## 5) Post-release Validation

- [ ] Test update path on a staging site via WP Admin -> Plugins -> Update Now
- [ ] Confirm "View version details" modal shows expected notes
- [ ] Confirm no unexpected errors in plugin logs
