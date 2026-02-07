# Changelog

## 2.7.1 - 2026-02-07

### Compatibility
- Declare PrestaShop 9 support by widening the supported version range to 9.x.
- Harden PHP 8.4 string handling by avoiding `trim()`/`strtolower()` on null values in front-office, back-office, and AJAX flows.
- Declare missing Context properties to avoid PHP 8.4 dynamic property deprecations.
- Unify front-office AJAX token handling with a generated secret token and tighten admin search endpoint validation.

### Maintenance
- Bump module version for the PS9/PHP 8.4 compatibility release.
