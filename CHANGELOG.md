# Changelog

All notable changes to `vimatech/laravel-integrations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-09-01

### Fixed

- The database idempotency store no longer reads every database failure as "this event was already handled". It caught `QueryException` unconditionally and returned false, which the webhook controller treats as a duplicate and skips; a missing table, a closed connection or a denied permission therefore produced `HTTP 200 {"processed": 0}`. The provider recorded a successful delivery and never redelivered, so the events were lost with nothing raised anywhere. Only an integrity constraint violation — SQLSTATE class 23, the unique index doing its job — now counts as a duplicate; anything else is re-thrown so the delivery fails and the provider retries.

### Changed

- An unrecognised `credentials.store` or `webhooks.event_store` is refused instead of silently selecting the weaker option. `credentials.store` fell through to the plaintext store, so a typo such as `encryped` read every credential in clear while the operator believed encryption was on; `webhooks.event_store` fell through from the durable database store to the cache store. Both now name the valid values and say what falling back would have cost. An application whose configuration is already correct is unaffected.

- The webhook events migration is published with `publishesMigrations()`, so the date in its filename is replaced with the time you publish it. It shipped as `0001_01_01_000000`, which sorts ahead of every migration an application can write and forced the package's table to be created first in the run order. Keeping a date in the filename is what makes the substitution possible: the framework replaces an existing date pattern and never adds a missing one, so a date-less filename would opt out of the mechanism rather than into it.

  This depends on `migrations.update_date_on_publish` being present in your `config/database.php`. Applications upgraded from Laravel 10 may not have that key, and without it the original date is kept.

### Upgrading

**Do not re-publish the migration if you have already run it.** Publishing again writes a second file, dated at the time you publish, that creates a table you already have; `migrate` then fails on it. Only publish on an installation that has never published it. Nothing changes for an installation that upgrades without re-publishing.

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
