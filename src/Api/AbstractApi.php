<?php

declare(strict_types=1);

namespace CloudPayments\Api;

use CloudPayments\Exception\ApiException;
use CloudPayments\Http\Envelope;
use CloudPayments\Http\Transport;

/**
 * Shared behaviour for the resource-specific API gateways: envelope
 * interpretation that turns API-level failures into {@see ApiException} while
 * leaving business outcomes (declines, 3DS) for callers to inspect.
 */
abstract class AbstractApi
{
    public function __construct(protected readonly Transport $transport)
    {
    }

    /**
     * Return the response `Model`. A failed call that carries no model (bad
     * parameters, unknown entity) is escalated to an exception; a failed call
     * that *does* carry a model (e.g. a declined transaction) is returned as-is
     * for the caller to interpret.
     *
     * @return array<array-key, mixed>
     */
    protected function model(Envelope $envelope): array
    {
        if (!$envelope->success && $envelope->model === []) {
            throw $this->failure($envelope);
        }

        return $envelope->model;
    }

    /**
     * Assert a plain envelope-only success (confirm, void, cancel, …).
     */
    protected function ensureSuccess(Envelope $envelope): void
    {
        if (!$envelope->success) {
            throw $this->failure($envelope);
        }
    }

    private function failure(Envelope $envelope): ApiException
    {
        return new ApiException(
            $envelope->message ?? 'CloudPayments API request failed.',
            $envelope->httpStatusCode,
            $envelope->raw,
        );
    }
}
