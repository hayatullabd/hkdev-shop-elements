# HKDEV Architecture Migration Map

This plugin now uses canonical namespaces and folder entry points while
preserving backward compatibility with legacy underscored class names.

## Canonical Namespaces

- `HkdevShopElements\Includes\Core\*`
- `HkdevShopElements\Includes\Admin\*`
- `HkdevShopElements\Includes\Widgets\*` (canonical class names via aliases)

## Compatibility Strategy

- Legacy implementation files stay in `includes/*.php` and `includes/widgets/*.php`.
- Canonical entry files live under:
  - `includes/Core/*.php`
  - `includes/Admin/*.php`
- Canonical entry files register aliases from legacy classes to canonical names.
- Widget registration uses canonical class names and falls back to legacy safely.

## Class Map (Legacy -> Canonical)

### Core Engines

- `Includes\Shop_Engine` -> `Includes\Core\ShopEngine`
- `Includes\Single_Product_Engine` -> `Includes\Core\SingleProductEngine`
- `Includes\Header_Engine` -> `Includes\Core\HeaderEngine`
- `Includes\Footer_Engine` -> `Includes\Core\FooterEngine`
- `Includes\Cart_Engine` -> `Includes\Core\CartEngine`
- `Includes\Checkout_Engine` -> `Includes\Core\CheckoutEngine`
- `Includes\Contact_Form_Engine` -> `Includes\Core\ContactFormEngine`
- `Includes\Catalog_Engine` -> `Includes\Core\CatalogEngine`
- `Includes\Review_Engine` -> `Includes\Core\ReviewEngine`
- `Includes\Video_Engine` -> `Includes\Core\VideoEngine`
- `Includes\Auth_Engine` -> `Includes\Core\AuthEngine`
- `Includes\Account_Engine` -> `Includes\Core\AccountEngine`
- `Includes\Tracking_Engine` -> `Includes\Core\TrackingEngine`
- `Includes\Page404_Engine` -> `Includes\Core\Page404Engine`
- `Includes\Blog_Engine` -> `Includes\Core\BlogEngine`

### Admin

- `Includes\Admin_Menu` -> `Includes\Admin\AdminMenu`
- `Includes\Checkout_Options` -> `Includes\Admin\CheckoutOptions`
- `Includes\Header_Options` -> `Includes\Admin\HeaderOptions`
- `Includes\Footer_Options` -> `Includes\Admin\FooterOptions`
- `Includes\Contact_Form_Options` -> `Includes\Admin\ContactFormOptions`
- `Includes\Review_Options` -> `Includes\Admin\ReviewOptions`
- `Includes\Widget_Options` -> `Includes\Admin\WidgetOptions`
- `Includes\Widget_Manager` -> `Includes\Admin\WidgetManager`
- `Includes\Admin\Plugin_Row` -> `Includes\Admin\PluginRow`

### Widgets (registration aliases)

Examples:

- `Includes\Widgets\Shop_Widget` -> `Includes\Widgets\ShopWidget`
- `Includes\Widgets\Header_Widget` -> `Includes\Widgets\HeaderWidget`
- `Includes\Widgets\Policy_Link_Base_Widget` -> `Includes\Widgets\PolicyLinkBaseWidget`

## Coding Guideline (New Work)

When adding new modules:

1. Add legacy implementation only if needed for compatibility.
2. Add canonical entry under `includes/Core` or `includes/Admin`.
3. Reference canonical class names from bootstrap and runtime code.
4. Keep aliases until a major version removes legacy classes.

## Migration Progress Snapshot

- Bootstrap uses canonical Core/Admin entry points.
- Widgets register via canonical class names with legacy fallbacks.
- Internal runtime calls are canonicalized (legacy names are compatibility-only).

## Audit Script

Run this before releases to ensure internal code does not re-introduce legacy
runtime references:

- `php scripts/migration-audit.php`
- `php scripts/migration-audit.php --strict`

Convenience runners:

- Bash: `./scripts/audit-migration.sh`
- Bash strict: `./scripts/audit-migration.sh --strict`
- PowerShell: `.\scripts\audit-migration.ps1`
- PowerShell strict: `.\scripts\audit-migration.ps1 -Strict`

Configuration file:

- `scripts/migration-audit.config.json`

CI workflow:

- `.github/workflows/migration-audit.yml`

Release gate:

- `.github/workflows/release.yml` runs strict migration audit before building
  and uploading the release zip.

PR gate support:

- `.github/PULL_REQUEST_TEMPLATE.md` includes migration/audit checklist prompts.
- `.github/CODEOWNERS` routes migration-critical changes to maintainers.
