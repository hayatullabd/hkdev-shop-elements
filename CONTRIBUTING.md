# Contributing to HKDEV Shop Elements

Thanks for contributing.

This repository uses a canonical architecture migration plan with compatibility
aliases. Please follow the process below so changes remain stable and release-safe.

## Branch and PR flow

1. Create a feature/fix branch from `main`.
2. Make focused commits with clear messages.
3. Open a pull request and complete `.github/PULL_REQUEST_TEMPLATE.md`.
4. Ensure required CODEOWNERS review is requested/approved where applicable.

## Architecture rules

- Prefer canonical classes/namespaces:
  - `HkdevShopElements\Includes\Core\*`
  - `HkdevShopElements\Includes\Admin\*`
  - Canonical widget class names (with compatibility aliases as needed)
- Do not introduce new legacy runtime references.
- Keep backward compatibility unless the change is planned for a major release.

Reference:

- `docs/ARCHITECTURE-MIGRATION.md`
- `docs/LEGACY-CLEANUP-CANDIDATES.md`

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
