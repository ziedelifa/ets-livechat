# ETS Live Chat (ets_livechat)

## Supported versions
- PrestaShop **9.0.1** (primary target)
- PrestaShop **8.x** (best-effort compatibility)
- PHP **8.4**

## Installation
1. Upload the `ets_livechat` module ZIP in your Back Office.
2. Install the module from **Modules > Module Manager**.
3. Configure the module in **Live Chat and Support**.

## Upgrade
1. Upload the updated ZIP over the existing module.
2. Run the upgrade from **Module Manager** (no data loss).
3. Clear cache if needed.

Existing data (tickets, conversations, messages, configuration) is preserved.

## Cron usage
The module provides a front controller cron entry point:

- **URL**: `https://yourshop.tld/module/ets_livechat/cron`
- **CLI** (example):
  ```bash
  php -d detect_unicode=0 /path/to/prestashop/index.php fc=module module=ets_livechat controller=cron
  ```

If you rely on a token or IP restriction in your environment, apply it at the server level (web server or firewall).

## Notes
- The module uses legacy ModuleAdminController tabs for Back Office pages, which remain supported in PrestaShop 9.
- AJAX endpoints validate Back Office context using admin tokens and Front Office requests using module-specific tokens.
- Front Office AJAX now uses a secret token stored in configuration (`ETS_LC_FO_TOKEN`), generated automatically on install/upgrade.
