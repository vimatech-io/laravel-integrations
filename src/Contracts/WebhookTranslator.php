<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Contracts;

use Illuminate\Http\Request;
use Vimatech\Integrations\Webhooks\CanonicalEvent;

/**
 * Translates a provider-specific inbound webhook into canonical events.
 *
 * An adapter may implement this directly, or a capability may configure a
 * dedicated translator class.
 */
interface WebhookTranslator
{
    /**
     * Verify the authenticity of the request (signature, shared secret, ...).
     */
    public function verify(Request $request): bool;

    /**
     * Translate the request into zero or more canonical events.
     *
     * @return iterable<CanonicalEvent>
     */
    public function translate(Request $request): iterable;
}
