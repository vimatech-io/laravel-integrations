<?php

declare(strict_types=1);

use Vimatech\Integrations\Contracts\CredentialStore;
use Vimatech\Integrations\Contracts\EventKeyStore;
use Vimatech\Integrations\Credentials\ConfigCredentialStore;
use Vimatech\Integrations\Credentials\EncryptedCredentialStore;
use Vimatech\Integrations\Webhooks\CacheEventKeyStore;

it('refuses a credential store name it does not know', function (): void {
    config()->set('integrations.credentials.store', 'encryped');
    app()->forgetInstance(CredentialStore::class);

    expect(fn () => app(CredentialStore::class))
        ->toThrow(InvalidArgumentException::class, 'read your credentials in clear');
});

it('refuses a webhook event store name it does not know', function (): void {
    config()->set('integrations.webhooks.event_store', 'databse');
    app()->forgetInstance(EventKeyStore::class);

    expect(fn () => app(EventKeyStore::class))
        ->toThrow(InvalidArgumentException::class, 'downgrade idempotency');
});

it('still resolves the stores it does know', function (): void {
    config()->set('integrations.credentials.store', 'config');
    config()->set('integrations.webhooks.event_store', 'cache');
    app()->forgetInstance(CredentialStore::class);
    app()->forgetInstance(EventKeyStore::class);

    expect(app(CredentialStore::class))->toBeInstanceOf(ConfigCredentialStore::class)
        ->and(app(EventKeyStore::class))->toBeInstanceOf(CacheEventKeyStore::class);

    config()->set('integrations.credentials.store', 'encrypted');
    app()->forgetInstance(CredentialStore::class);

    expect(app(CredentialStore::class))->toBeInstanceOf(EncryptedCredentialStore::class);
});
