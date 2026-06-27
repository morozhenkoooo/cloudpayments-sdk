<?php

declare(strict_types=1);

namespace CloudPayments\Api;

use CloudPayments\Http\BodyFormat;
use CloudPayments\Request\Receipt\CreateReceiptRequest;
use CloudPayments\Response\Receipt;

/**
 * 54-FZ Receipts API: register fiscal receipts and look them up by id.
 *
 * Every endpoint under `/kkt/*` takes a JSON request body, so all calls use
 * {@see BodyFormat::Json}.
 */
final class ReceiptsApi extends AbstractApi
{
    /**
     * Register a fiscal receipt (`/kkt/receipt`).
     */
    public function create(CreateReceiptRequest $request, ?string $requestId = null): Receipt
    {
        $envelope = $this->transport->send('/kkt/receipt', $request, BodyFormat::Json, $requestId);

        return Receipt::fromModel($this->model($envelope));
    }

    /**
     * Fetch the fiscalization status of a receipt by its id.
     */
    public function getStatus(string $id): Receipt
    {
        $envelope = $this->transport->send('/kkt/receipt/status/get', ['Id' => $id], BodyFormat::Json);

        return Receipt::fromModel($this->model($envelope));
    }

    /**
     * Fetch a registered receipt by its id.
     */
    public function get(string $id): Receipt
    {
        $envelope = $this->transport->send('/kkt/receipt/get', ['Id' => $id], BodyFormat::Json);

        return Receipt::fromModel($this->model($envelope));
    }
}
