# Contributing to HKDEV Shop Elements

Thanks for contributing.

This repository uses canonical namespaces only (1.0.0+). Please follow the
process below so changes remain stable and release-safe.

## Branch and PR flow

1. Create a feature/fix branch from `main`.
2. Make focused commits with clear messages.
3. Open a pull request and complete `.github/PULL_REQUEST_TEMPLATE.md`.
4. Ensure required CODEOWNERS review is requested/approved where applicable.

## Architecture rules

- Use canonical classes/namespaces:
  - `HkdevShopElements\Includes\Core\*`
  - `HkdevShopElements\Includes\Admin\*`
  - `HkdevShopElements\Includes\Widgets\*` (`ShopWidget`, not `Shop_Widget`)
- Do not introduce underscored legacy class names.

Reference:

- `docs/ARCHITECTURE-MIGRATION.md`

## Required local checks

Run before opening/updating a PR:

```sh
php scripts/migration-audit.php
php scripts/migration-audit.php --strict
```

If `php` is not available in your shell:

- Bash: `./scripts/audit-migration.sh --strict`
- PowerShell: `.\scripts\audit-migration.ps1 -Strict`

Optional (recommended when available):

```sh
php scripts/boot-smoke-test.php
```

## Release-related contributions

If your PR affects release behavior, versioning, CI workflows, or migration
governance:

- Follow `docs/RELEASE-CHECKLIST.md`
- Keep notes aligned with `.github/RELEASE_NOTES_TEMPLATE.md`

Release automation already runs:

- strict migration audit in `.github/workflows/migration-audit.yml`
- strict migration gate before zip packaging in `.github/workflows/release.yml`
