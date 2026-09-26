# Legacy Cleanup Candidates

This file tracks what can be removed in a future major version after the
canonical class migration is fully adopted by downstream code.

## Current Status

- Internal runtime references now use canonical classes (`Includes\Core\*`,
  `Includes\Admin\*`, canonical widget names).
- Legacy underscored classes remain only as compatibility providers.
- Compatibility is currently implemented via `class_alias()` and shim files.
- Direct legacy static/instance runtime calls in plugin internals have been
  removed (remaining legacy names are definitions, comments, and aliases).

## Safe Removal Scope (Major Release)

Remove these only in a breaking-change release:

### 1) Legacy root implementation classes (underscored names)

Files under `includes/` that still define classes like:

- `Shop_Engine`, `Single_Product_Engine`, `Header_Engine`, `Footer_Engine`
- `Cart_Engine`, `Checkout_Engine`, `Contact_Form_Engine`, `Catalog_Engine`
- `Review_Engine`, `Video_Engine`, `Auth_Engine`, `Account_Engine`
- `Tracking_Engine`, `Page404_Engine`, `Blog_Engine`
- `Admin_Menu`, `Checkout_Options`, `Header_Options`, `Footer_Options`
- `Contact_Form_Options`, `Review_Options`, `Widget_Options`, `Widget_Manager`

Prerequisite: move actual class definitions to canonical files first, then keep
temporary reverse aliases for one transition release if needed.

### 2) Compatibility wrapper files

- `includes/Core/*.php` alias wrappers
- `includes/Admin/*.php` alias wrappers
- `includes/plugin-row.php` legacy shim
- `includes/admin/plugin-row.php` path-compat shim

These can be collapsed once canonical classes are native definitions instead of
aliases and no external integration uses legacy class names.

### 3) Legacy widget class names with underscores

Widget files currently define legacy names (e.g. `Shop_Widget`) while
registration uses canonical names (e.g. `ShopWidget`) via alias map.

Future major cleanup can:

1. Rename class definitions in widget files to canonical names.
2. Keep reverse alias map for one release window.
3. Remove reverse aliases after adoption period.

## Pre-removal Checklist

- Search for legacy references:
  - `HkdevShopElements\\Includes\\.*_`
  - `class .*_.*`
- Check external snippets/docs/changelog for old class names.
- Run smoke/bootstrap tests in an environment with PHP CLI available.
- Ship migration notes with old -> new class map.

## Non-Breaking Recommendation (Now)

Keep current aliases in place for backward compatibility. They are low-overhead
and allow gradual migration without runtime regressions.
