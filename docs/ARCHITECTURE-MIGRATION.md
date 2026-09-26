# HKDEV Plugin Structure

Real class bodies live in canonical namespaces. Bootstrap loads those
files directly. This is a clean 1.0.0 tree — no legacy shims.

## Folder layout

```
hkdev-shop-elements.php          Bootstrap (loads Core + Admin modules)
includes/
  Core/                          Runtime engines
  Admin/                         WP Admin + Elementor registration
  widgets/                       Elementor widgets
  Support/                       error-logger, GitHub updater
assets/
  css/  js/
templates/
docs/
scripts/
```

## Canonical namespaces

- `HkdevShopElements\Includes\Core\*`
- `HkdevShopElements\Includes\Admin\*`
- `HkdevShopElements\Includes\Widgets\*`

## Coding guideline

1. Add new engines under `includes/Core`.
2. Add new admin modules under `includes/Admin`.
3. Reference canonical class names from bootstrap and runtime code.

## Audit

```
php scripts/migration-audit.php --strict
```

PowerShell: `.\scripts\audit-migration.ps1 -Strict`
