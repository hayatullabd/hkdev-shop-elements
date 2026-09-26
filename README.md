# HKDEV Shop Elements

Standalone Elementor + WooCommerce widgets that work with **any** WordPress theme
(no dependency on the hkdev-shop theme):

- Shop Grid / Trending carousel
- Cart
- Checkout (custom one-page, admin-managed fields)
- Single Product
- Header / Footer (site-wide builders)
- Catalog (search + filter + sort)
- Category Grid / Carousel
- Customer Reviews (Video Reviews / Social Proofs tabs, modals, Top Pick product promo)
- Video Embed (lite YouTube poster + click-to-play)
- Hero Slider (full-width banner slider: arrows, dots, autoplay progress, swipe)
- Contact Form
- Auth (login / register), My Account, Order Tracking, 404

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce 7.0+
- Elementor (for the widgets; the shortcodes work without it)

## Install

This is **1.1.0**. Update from 1.0.x via WP Admin → Plugins → Update Now, or
install the release zip. For a first install from 0.5.x, delete the old plugin
folder first — do not overwrite it in place.

1. WP Admin → Plugins → deactivate and delete **HKDEV Shop Elements**.
2. Download the `hkdev-shop-elements.zip` release asset (or build it with `scripts/build-release-zip.sh`).
3. WP Admin → Plugins → Add New → Upload Plugin → choose the ZIP → Install → Activate.
4. Configure under **WP Admin → HKDEV Shop** (Checkout Fields, Header, Footer, Contact Form).

Elementor widget slugs (`hkdev_shop_grid`, `hkdev_shop_carousel`, …) are
unchanged, so existing pages keep their widgets after the new install.

## Automatic updates from GitHub

The plugin ships its own update checker (`includes/Support/github-updater.php`), so
**WP Admin → Plugins → Update Now** pulls the newest code straight from GitHub.
No wordpress.org, no manual re-upload.

### One-time setup

This repository is already wired up:

```php
define( 'HKDEV_ELEMENTS_GITHUB_REPO', 'hayatullabd/hkdev-shop-elements' );
```

…and the matching `Update URI:` header at the top of `hkdev-shop-elements.php`.
The repository is **public**. To point the plugin at a different repository,
change both values. Remove `includes/Support/github-updater.php`, its loader in
`hkdev-shop-elements.php` and the `Update URI:` header if you don't want the
feature.

### Publishing an update

1. Bump the version in **both** places at the top of `hkdev-shop-elements.php`:
   - the `Version:` header
   - `define( 'HKDEV_ELEMENTS_VERSION', '...' )`
2. Run migration safety checks:

   ```sh
   php scripts/migration-audit.php --strict
   ```

   If `php` is not available in your shell, use:

   - `./scripts/audit-migration.sh --strict` (Bash)
   - `.\scripts\audit-migration.ps1 -Strict` (PowerShell)

3. Commit and push.
4. Create a tag (optionally a full GitHub Release with notes):

   ```sh
   git tag v1.0.0
   git push origin v1.0.0
   ```

Every site running the plugin now shows "There is a new version available" and
updates with one click. Release notes written in the GitHub Release appear in
the "View version details" popup.

> Tip: attach a `.zip` asset to the Release if you want full control over the
> archive contents; otherwise GitHub's auto-generated source ZIP is used and the
> updater renames the folder for you.

Use `docs/RELEASE-CHECKLIST.md` before tagging, and
`.github/RELEASE_NOTES_TEMPLATE.md` for consistent release notes.

If an update does not appear immediately, open **Dashboard → Updates** and
click **Check again** (the GitHub API response is cached for 6 hours).

## File layout

```
hkdev-shop-elements.php   Plugin bootstrap (header, constants, updater loader)
includes/                 Engines, admin screens, GitHub updater
includes/widgets/         Elementor widgets
assets/css|js/            Front-end assets (a .min.* next to a file is served first)
```

## Contributing

See `CONTRIBUTING.md` for PR flow, migration audit requirements, and release
governance rules.
