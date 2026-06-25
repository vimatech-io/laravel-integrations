<?php

declare(strict_types=1);

namespace Vimatech\Integrations\Contracts;

/**
 * Marker interface implemented by every integration adapter.
 *
 * Capability-specific behaviour (the actual methods an adapter exposes) is
 * defined by contracts that EXTEND this interface in consumer packages. This
 * package intentionally knows nothing about concrete capabilities.
 */
interface Driver {}
