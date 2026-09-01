<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vimatech\Integrations\Webhooks\CacheEventKeyStore;
use Vimatech\Integrations\Webhooks\DatabaseEventKeyStore;

it('acquires a cache key once', function (): void {
    $store = new CacheEventKeyStore(cache()->store('array'));

    expect($store->acquire('evt-1', 60))->toBeTrue()
        ->and($store->acquire('evt-1', 60))->toBeFalse()
        ->and($store->acquire('evt-2', 60))->toBeTrue();
});

it('acquires a database key once', function (): void {
    Schema::create('integration_webhook_events', function (Blueprint $table): void {
        $table->id();
        $table->string('key')->unique();
        $table->timestamp('created_at')->nullable();
    });

    $store = new DatabaseEventKeyStore(app('db')->connection(), 'integration_webhook_events');

    expect($store->acquire('evt-1', 60))->toBeTrue()
        ->and($store->acquire('evt-1', 60))->toBeFalse()
        ->and($store->acquire('evt-2', 60))->toBeTrue();
});

it('reports a database failure instead of calling the event a duplicate', function (): void {
    // no table created: the insert fails for a reason that is not a duplicate key
    $store = new DatabaseEventKeyStore(app('db')->connection(), 'integration_webhook_events');

    expect(fn () => $store->acquire('evt-1', 60))
        ->toThrow(QueryException::class);
});
