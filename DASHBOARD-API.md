# 1Tool Dashboard reporting API contract

`1Plugin` can report installed-site telemetry to `1Tool Dashboard`.

The plugin-side sender is implemented in `OnePlugin_Light_Site_Tools` and posts a heartbeat through WP-Cron. The heartbeat runs every 15 minutes, with the first event scheduled about 1 minute after activation/update, depending on WP-Cron traffic.

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
    "version": "2.9.81",
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
    "last_dashboard_report": {},
    "dashboard_command_results": [],
    "internal": {
      "wp_cron": {
        "disabled": false,
        "next_dashboard_heartbeat_at": "2026-08-10T08:45:00+00:00",
        "schedule": "oneplugin_light_every_15_minutes"
      },
      "memory": {
        "limit": "256M",
        "usage_mb": 42.5,
        "peak_usage_mb": 48
      },
      "debug": {
        "wp_debug": false,
        "wp_debug_log": false,
        "wp_debug_display": false
      },
      "outbound_http": {
        "last_dashboard_report_ok": true,
        "last_dashboard_status_code": 200,
        "last_dashboard_message": "OK"
      },
      "recent_errors": []
    }
  },
  "updates": {
    "plugins": {
      "available_count": 1,
      "items": [
        {
          "plugin": "example-plugin/example-plugin.php",
          "name": "Example Plugin",
          "version": "1.0.0",
          "new_version": "1.1.0",
          "active": true
        }
      ]
    },
    "themes": {
      "available_count": 0,
      "items": []
    },
    "core": {
      "update_available": false,
      "version": "6.8.2",
      "new_version": "",
      "response": "latest"
    }
  },
  "dashboard_commands": {
    "enabled": true,
    "supported_types": [
      "update_company_data",
      "update_contact_data"
    ],
    "writable_settings": [
      "company_name",
      "street_address",
      "postal_code",
      "city",
      "phone_primary",
      "organization_number",
      "email",
      "form_email",
      "website",
      "facebook_url",
      "instagram_url",
      "linkedin_url",
      "youtube_url",
      "x_url",
      "reddit_url",
      "booking_url"
    ]
  },
  "company_data": {
    "company_name": "Example Company AB",
    "street_address": "Example Street 1",
    "postal_code": "123 45",
    "city": "Stockholm",
    "phone_primary": "+46 70 000 00 00",
    "organization_number": "559000-0000",
    "email": "info@example.se",
    "form_email": "forms@example.se",
    "website": "https://example.se/",
    "facebook_url": "",
    "instagram_url": "",
    "linkedin_url": "",
    "youtube_url": "",
    "x_url": "",
    "reddit_url": "",
    "booking_url": ""
  },
  "capabilities": {
    "settings_read": true,
    "settings_write": true,
    "divi_sync": true,
    "keyword_meta": true,
    "dashboard_reporting": true,
    "dashboard_commands": true,
    "internal_health": true,
    "update_notifications": true
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
- `updated`: `plugin.version >= 2.9.81`
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

## Dashboard commands

The dashboard may return commands in the heartbeat response. Commands are pull-based: the dashboard does not call the WordPress site directly.

The plugin sends the current business/contact values in `company_data` on every heartbeat. The dashboard should import this as the initial/current snapshot only. It must not automatically send this data back as an update command. Create a command only after an explicit user save/sync action in the dashboard.

Example accepted response:

```json
{
  "ok": true,
  "commands": [
    {
      "id": "cmd_20260810_001",
      "type": "update_company_data",
      "payload": {
        "company_name": "New Company AB",
        "street_address": "New Street 10",
        "postal_code": "123 45",
        "city": "Stockholm",
        "phone_primary": "+46 70 000 00 00",
        "email": "info@example.se",
        "organization_number": "559000-0000"
      }
    }
  ]
}
```

Supported command types:

- `update_company_data`
- `update_contact_data`

Writable fields are strictly whitelisted:

- `company_name`
- `street_address`
- `postal_code`
- `city`
- `phone_primary`
- `organization_number`
- `email`
- `form_email`
- `website`
- `facebook_url`
- `instagram_url`
- `linkedin_url`
- `youtube_url`
- `x_url`
- `reddit_url`
- `booking_url`

The plugin rejects unsupported command types and ignores fields outside the whitelist. Command IDs are remembered locally, so the same command is not applied twice.

Command results are sent back in the next heartbeat under `health.dashboard_command_results`:

```json
{
  "id": "cmd_20260810_001",
  "type": "update_company_data",
  "status": "applied",
  "message": "Settings updated.",
  "applied_at": "2026-08-10T08:30:00+00:00",
  "changed_fields": ["street_address"]
}
```

Possible command statuses:

- `applied`
- `skipped`
- `rejected`

## Read-only health and update notifications

The plugin may report safe read-only health data under `health.internal`.

This data is informational only. The dashboard must not treat it as permission to run updates or remote actions.

Safe internal health fields:

- WP-Cron disabled flag
- next dashboard heartbeat timestamp
- memory limit / usage / peak usage
- WP debug flags
- last outbound dashboard report result
- recent fatal PHP errors, sanitized and truncated

The plugin may also report read-only update notifications under `updates`.

Supported update notification groups:

- `updates.plugins`
- `updates.themes`
- `updates.core`

These are read-only. The dashboard must display them as notifications only. It must not create update commands for plugins, themes, or WordPress core.

## External dashboard checks

Site uptime and visible performance should be checked by the dashboard, not by the plugin.

Recommended external checks:

- `GET site.home_url`
- `GET site.site_url + /wp-json/`

Recommended constraints:

- no credentials
- no admin URLs
- timeout around 5 seconds
- interval 5-15 minutes
- store status code, response time, SSL status, last successful check, consecutive failures

Suggested dashboard-only statuses:

- `online`
- `slow`
- `failing`
- `offline_suspected`
- `stale_heartbeat`
