<?php

declare(strict_types=1);

namespace CloudPayments\Api;

use CloudPayments\Http\Envelope;
use CloudPayments\Request\Payment\CardPaymentRequest;
use CloudPayments\Request\Payment\ConfirmRequest;
use CloudPayments\Request\Payment\Post3dsRequest;
use CloudPayments\Request\Payment\RefundRequest;
use CloudPayments\Request\Payment\TokenPaymentRequest;
use CloudPayments\Response\Refund;
use CloudPayments\Response\Secure3DS;
use CloudPayments\Response\Transaction;

/**
 * Payments API: single- and two-stage card/token payments, 3DS completion,
 * refunds, voids, and lookups.
 *
 * Charge/auth methods return {@see Transaction}|{@see Secure3DS}: inspect the
 * result with `instanceof Secure3DS` to branch into the 3DS flow.
 */
final class PaymentsApi extends AbstractApi
{
    /**
     * Single-stage payment by cryptogram (authorize + capture at once).
     */
    public function charge(CardPaymentRequest $request, ?string $requestId = null): Transaction|Secure3DS
    {
        return $this->paymentResult($this->transport->send('/payments/cards/charge', $request, requestId: $requestId));
    }

    /**
     * Two-stage payment by cryptogram (authorize only; capture later with confirm).
     */
    public function auth(CardPaymentRequest $request, ?string $requestId = null): Transaction|Secure3DS
    {
        return $this->paymentResult($this->transport->send('/payments/cards/auth', $request, requestId: $requestId));
    }

    /**
     * Single-stage payment by a saved card token (server-initiated).
     */
    public function chargeToken(TokenPaymentRequest $request, ?string $requestId = null): Transaction|Secure3DS
    {
        return $this->paymentResult($this->transport->send('/payments/tokens/charge', $request, requestId: $requestId));
    }

    /**
     * Two-stage payment by a saved card token.
     */
    public function authToken(TokenPaymentRequest $request, ?string $requestId = null): Transaction|Secure3DS
    {
        return $this->paymentResult($this->transport->send('/payments/tokens/auth', $request, requestId: $requestId));
    }

    /**
     * Complete a payment after the 3-D Secure challenge.
     */
    public function post3ds(Post3dsRequest $request, ?string $requestId = null): Transaction
    {
        return Transaction::fromModel($this->model($this->transport->send('/payments/cards/post3ds', $request, requestId: $requestId)));
    }

    /**
     * Capture (confirm) a previously authorized two-stage payment.
     */
    public function confirm(ConfirmRequest $request, ?string $requestId = null): void
    {
        $this->ensureSuccess($this->transport->send('/payments/confirm', $request, requestId: $requestId));
    }

    /**
     * Release the hold on an authorized-but-not-captured payment.
     */
    public function void(int $transactionId, ?string $requestId = null): void
    {
        $this->ensureSuccess($this->transport->send('/payments/void', ['TransactionId' => $transactionId], requestId: $requestId));
    }

    /**
     * Refund a completed payment (full or partial). Returns the refund transaction.
     */
    public function refund(RefundRequest $request, ?string $requestId = null): Refund
    {
        return Refund::fromModel($this->model($this->transport->send('/payments/refund', $request, requestId: $requestId)));
    }

    /**
     * Fetch a transaction by its id.
     */
    public function get(int $transactionId): Transaction
    {
        return Transaction::fromModel($this->model($this->transport->send('/payments/get', ['TransactionId' => $transactionId])));
    }

    /**
     * Find the latest transaction for a merchant InvoiceId, or null if none.
     *
     * Returns null only when no transaction matches (empty Model); a genuine API
     * failure (bad parameters, etc.) is raised as an {@see ApiException} rather
     * than masked as "not found".
     */
    public function findByInvoiceId(string $invoiceId): ?Transaction
    {
        $model = $this->model($this->transport->send('/payments/find', ['InvoiceId' => $invoiceId]));

        if ($model === []) {
            return null;
        }

        return Transaction::fromModel($model);
    }

    /**
     * List transactions for a single day.
     *
     * @return list<Transaction>
     */
    public function list(\DateTimeInterface $date, ?string $timeZone = null): array
    {
        $envelope = $this->transport->send('/payments/list', [
            'Date' => $date->format('Y-m-d'),
            'TimeZone' => $timeZone,
        ]);

        $this->ensureSuccess($envelope);

        $transactions = [];

        foreach ($envelope->model as $row) {
            if (\is_array($row)) {
                $transactions[] = Transaction::fromModel($row);
            }
        }

        return $transactions;
    }

    private function paymentResult(Envelope $envelope): Transaction|Secure3DS
    {
        $model = $this->model($envelope);

        if (isset($model['AcsUrl']) || isset($model['PaReq']) || isset($model['creq'])) {
            return Secure3DS::fromModel($model);
        }

        return Transaction::fromModel($model);
    }
}
