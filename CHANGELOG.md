# Changelog

All notable changes to `vimatech/laravel-integrations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-06-26

### Changed

- Restructure README to match package conventions (Feature Matrix, named Why section, removed redundant Complete Example).
- Add `.gitattributes` (`export-ignore`) and Packagist badges.

## [1.0.0] - 2026-06-26

### Added

- `IntegrationManager` — resolves drivers by capability + config key, with a custom-factory `extend()`
  hook and instance caching.
- `DriverRegistry` — read-only access to the configured capabilities, drivers, routing and webhook
  settings.
- `ContextRouter` (`Integrations::for($capability)`) — resolves a driver by default or by context
  array, with `resolve()`, `resolveStrict()`, `default()`, `via()` and `key()`.
- `ResolvesTenantDriver` contract for per-tenant driver overrides from the database.
- Generic inbound webhook pipeline: `WebhookTranslator` contract, signature verification, canonical
  event translation, idempotency via `EventKeyStore` (cache or database) and dispatch of
  `WebhookReceived` / `WebhookRejected` events plus translated `CanonicalEvent`s.
- Credential resolution via `CredentialStore` (`ConfigCredentialStore`, `EncryptedCredentialStore`),
  with an extension point for `vimatech/laravel-secure-fields`.
- `Integrations` facade with `driver()`, `for()`, `capabilities()`, `registry()`, `extend()` and
  `fake()`.
- `IntegrationsFake` test double with `assertDriverUsed()`, `assertDriverNotUsed()`,
  `assertNothingUsed()` and `used()`.
- `integrations:list` Artisan command.
- Publishable config and migration.

[1.0.0]: https://github.com/vimatech-io/laravel-integrations/releases/tag/v1.0.0
