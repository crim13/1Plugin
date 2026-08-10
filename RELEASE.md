# 1Plugin release checklist

This repository has two ZIP formats with different purposes. Do not mix them.

## Current stable baseline

- Last known stable public release: `v2.8.0`
- Current release target: `v2.9.79`
- GitHub repository: `crim13/1Plugin`
- GitHub updater asset name: `1plugin-light.zip`

## Version rules

Before building a public release:

1. `1plugin-light.php` plugin header must use the final stable version:
   `Version: 2.9.79`
2. `ONEPLUGIN_LIGHT_VERSION` must match exactly:
   `define('ONEPLUGIN_LIGHT_VERSION', '2.9.79');`
3. The GitHub tag must be `v2.9.79`.
4. Do not publish a `-dev`, `-test`, or `-rc` version as the stable latest release.

## ZIP formats

### WordPress manual upload test ZIP

Use this format only for testing via WordPress Admin -> Plugins -> Add New -> Upload Plugin.

```text
1plugin-light/
  1plugin-light.php
  includes/
  assets/
  modules/
```

The root folder name must match the installed plugin folder on the test site. If the site uses a different folder in `wp-content/plugins/`, build the manual ZIP with that folder name.

### GitHub release updater ZIP

Use this format for GitHub Releases and automatic updates.

The asset must be named exactly:

```text
1plugin-light.zip
```

The ZIP must be flat, matching the stable `v2.8.0` release structure:

```text
1plugin-light.php
README.md
DESCRIERE-PLUGIN.md
includes/
assets/
modules/
```

Do not wrap these files in a top-level `1plugin-light/` folder for the GitHub release asset.

## Runtime contents

Include:

- `1plugin-light.php`
- `README.md`
- `DESCRIERE-PLUGIN.md`
- `includes/`
- `assets/`
- `modules/README.md`
- `modules/menu/README.md`
- `modules/menu/module.json`
- `modules/menu/server/`
- `modules/menu/visual-builder/build/`
- `modules/faq/README.md`
- `modules/faq/module.json`
- `modules/faq/server/`
- `modules/faq/visual-builder/build/`

Exclude:

- `.git/`
- `.tools/`
- `node_modules/`
- `CodexLogs/`
- old `.zip` files
- Visual Builder source files unless intentionally needed for runtime
- package lock files unless intentionally needed for runtime

## Compatibility rules for 2.8 -> 2.9.79

- Keep legacy shortcodes registered:
  - `[formular]`
  - `[kundens_epost]`
- Keep `form_email` in settings and legacy migration.
- Do not auto-enable the FAQ extension for existing 2.8 sites.
- Do not remove a site from WordPress native plugin auto-updates just because the new plugin setting defaults to off.
- Keep GitHub updater asset lookup set to `1plugin-light.zip`.
- Keep dashboard reporting lightweight and non-critical. A failed dashboard report must never break frontend, admin, or plugin updates.

## Pre-release verification

Before creating the GitHub release:

1. Build the final flat `1plugin-light.zip`.
2. Inspect ZIP entries and confirm there is no top-level folder.
3. Confirm there are no excluded dev files.
4. Confirm `1plugin-light.php` inside the ZIP has version `2.9.79`.
5. Confirm PHP files parse with the available parser or `php -l`.
6. Test update from `v2.8.0` on at least one real site.
7. Verify admin, frontend, sticky footer, menu, shortcodes, and Divi compatibility.
8. Verify dashboard reporting settings exist and the local `/wp-json/oneplugin2/v1/status` endpoint exposes dashboard reporting state.

## GitHub release flow

1. Commit the exact source used to build the release ZIP.
2. Push the commit.
3. Create tag `v2.9.79` on that commit.
4. Create a GitHub release for `v2.9.79`.
5. Upload exactly one updater asset named `1plugin-light.zip`.
6. Publish it as a stable release and mark it as Latest.
7. Verify GitHub latest release returns `v2.9.79`.
8. Verify the release asset list contains `1plugin-light.zip`.

## Post-release verification

After publishing:

1. Open a site running `v2.8.0`.
2. Force WordPress update check.
3. Confirm WordPress sees `2.9.79`.
4. Update through the plugin updater.
5. Confirm the plugin stays in the same installed plugin folder.
6. Confirm the site still works.
7. Monitor dashboard/telemetry once available.
