# 1Plugin Light

Lightweight WordPress plugin with company data, shortcodes, sticky mobile footer, page keyword fields, custom code tools, and GitHub release updates.

## GitHub updater

The plugin checks the latest GitHub release and installs the release asset named `1plugin-light.zip`.

Before publishing releases, set the GitHub owner in `1plugin-light.php` or define it earlier in `wp-config.php`:

```php
define('ONEPLUGIN_LIGHT_GITHUB_OWNER', 'your-github-owner');
define('ONEPLUGIN_LIGHT_GITHUB_REPO', '1Plugin');
define('ONEPLUGIN_LIGHT_GITHUB_ASSET', '1plugin-light.zip');
```

Release flow:

1. Update the plugin version in the plugin header and `ONEPLUGIN_LIGHT_VERSION`.
2. Build `1plugin-light.zip` with the same archive structure as the installed plugin. Current site installs use a flat zip with `1plugin-light.php` and `includes/` directly at the zip root.
3. Create a GitHub release tagged like `v2.5.2`.
4. Upload `1plugin-light.zip` as a release asset.

Sites that already run a version without this updater need one manual install of this build. Future versions can then be updated through the WordPress plugins screen.
