# 1Tool Dashboard reporting API contract

`1Plugin` can report installed-site telemetry to `1Tool Dashboard`.

The plugin-side sender is implemented in `OnePlugin_Light_Site_Tools` and posts a heartbeat through WP-Cron.

## Endpoint

Default plugin endpoint:

```text
POST https://one-tool-dashboard.morosanu-cristian98.chatgpt.site/api/plugin-sites/report
```

The endpoint can be changed per site in the 1Plugin admin settings or with the filter:

```php
add_filter('oneplugin_light_dashboard_reporting_endpoint', function () {
    return 'https://example.com/api/plugin-sites/report';
});
```

## Optional bearer token

If the dashboard requires an ingestion token, define it in `wp-config.php`:

```php
define('ONEPLUGIN_LIGHT_DASHBOARD_TOKEN', 'token-here');
```

or provide it with:

```php
add_filter('oneplugin_light_dashboard_token', function () {
    return 'token-here';
});
```

When present, the plugin sends:

```text
Authorization: Bearer <token>
```

For the first public version, the endpoint may also accept unsigned reports because the data is low sensitivity and existing sites will not have a preconfigured token.

## Headers

```text
Content-Type: application/json
Accept: application/json
User-Agent: 1Plugin-Light-Dashboard/<plugin-version>
X-OnePlugin-Site-UUID: <site_uuid>
X-OnePlugin-Version: <plugin-version>
```

## Payload

```json
{
  "schema_version": 1,
  "reported_at": "2026-08-10T08:20:00+00:00",
  "site_uuid": "uuid",
  "site": {
    "name": "Site name",
    "home_url": "https://example.com/",
    "site_url": "https://example.com/",
    "admin_url": "https://example.com/wp-admin/",
    "locale": "sv_SE",
    "timezone": "Europe/Stockholm",
    "is_multisite": false
  },
  "plugin": {
    "name": "1Plugin",
    "version": "2.9.79",
    "api_version": "1",
    "update_uri": "https://github.com/crim13/1Plugin"
  },
  "environment": {
    "wp_version": "6.8.2",
    "php_version": "8.2.0",
    "wp_debug": false,
    "ssl": true
  },
  "theme": {
    "name": "Divi",
    "stylesheet": "Divi",
    "template": "Divi",
    "version": "5.x"
  },
  "features": {
    "faq_extension_enabled": false,
    "module_menu_enabled": true,
    "module_faq_enabled": false,
    "github_auto_updates_setting_enabled": false,
    "dashboard_reporting_enabled": true
  },
  "health": {
    "status": "ok",
    "last_dashboard_report": {}
  }
}
```

## Dashboard storage model

Recommended primary key:

```text
site_uuid
```

Recommended fields:

- `site_uuid`
- `site_name`
- `home_url`
- `site_url`
- `plugin_version`
- `wp_version`
- `php_version`
- `theme_name`
- `theme_version`
- `health_status`
- `last_seen_at`
- `first_seen_at`
- `last_payload`

## Dashboard health rules

Suggested dashboard status:

- `ok`: latest report received and `health.status = ok`
- `updated`: `plugin.version >= 2.9.79`
- `needs_update`: report received but plugin version is below target
- `stale`: no report for more than 48 hours
- `unknown`: site exists in dashboard but has not reported yet

If a site breaks fatally after update, it may stop reporting. The dashboard should treat missing/stale heartbeats as a problem signal.

## Expected response

The dashboard should return `2xx` for accepted reports:

```json
{
  "ok": true
}
```

Any non-2xx response is stored by the plugin as the last dashboard report failure but does not break the WordPress site.
