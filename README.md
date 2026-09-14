# HKDEV Shop Elements

Standalone Elementor + WooCommerce widgets that work with **any** WordPress theme
(no dependency on the hkdev-shop theme):

- Shop Grid / Trending carousel
- Cart
- Checkout (custom one-page, admin-managed fields)
- Single Product
- Header / Footer (site-wide builders)
- Catalog (search + filter + sort)
- Contact Form
- Auth (login / register), My Account, Order Tracking, 404

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce 7.0+
- Elementor (for the widgets; the shortcodes work without it)

## Install

1. Download the repository (Code → Download ZIP) or a release asset ZIP.
2. WP Admin → Plugins → Add New → Upload Plugin → choose the ZIP → Install → Activate.
3. Configure under **WP Admin → HKDEV Shop** (Checkout Fields, Header, Footer, Contact Form).

## Automatic updates from GitHub

The plugin ships its own update checker (`includes/github-updater.php`), so
**WP Admin → Plugins → Update Now** pulls the newest code straight from GitHub.
No wordpress.org, no manual re-upload.

### One-time setup

This repository is already wired up:

```php
define( 'HKDEV_ELEMENTS_GITHUB_REPO', 'hayatullabd/hkdev-shop-elements' );
```

…and the matching `Update URI:` header at the top of `hkdev-shop-elements.php`.
The repository is **public**. To point the plugin at a different repository,
change both values. Remove `includes/github-updater.php`, its loader in
`hkdev-shop-elements.php` and the `Update URI:` header if you don't want the
feature.

### Publishing an update

1. Bump the version in **both** places at the top of `hkdev-shop-elements.php`:
   - the `Version:` header
   - `define( 'HKDEV_ELEMENTS_VERSION', '...' )`
2. Commit and push.
3. Create a tag (optionally a full GitHub Release with notes):

   ```sh
   git tag v0.2.1
   git push origin v0.2.1
   ```

Every site running the plugin now shows "There is a new version available" and
updates with one click. Release notes written in the GitHub Release appear in
the "View version details" popup.

> Tip: attach a `.zip` asset to the Release if you want full control over the
> archive contents; otherwise GitHub's auto-generated source ZIP is used and the
> updater renames the folder for you.

If an update does not appear immediately, open **Dashboard → Updates** and
click **Check again** (the GitHub API response is cached for 6 hours).

## File layout

```
hkdev-shop-elements.php   Plugin bootstrap (header, constants, updater loader)
includes/                 Engines, admin screens, GitHub updater
includes/widgets/         Elementor widgets
assets/css|js/            Front-end assets (a .min.* next to a file is served first)
```
