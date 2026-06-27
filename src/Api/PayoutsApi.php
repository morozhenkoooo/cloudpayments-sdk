<?php

declare(strict_types=1);

namespace CloudPayments\Api;

use CloudPayments\Request\Payout\CardPayoutRequest;
use CloudPayments\Request\Payout\TokenPayoutRequest;
use CloudPayments\Response\Payout;

/**
 * Payouts API: transfer funds to a card by cryptogram or to a saved card token
 * (topup).
 *
 * Note the asymmetric paths: the card endpoint is plural (`/payments/cards/topup`)
 * while the token endpoint is singular (`/payments/token/topup`).
 */
final class PayoutsApi extends AbstractApi
{
    /**
     * Pay out to a card by cryptogram.
     */
    public function toCard(CardPayoutRequest $request, ?string $requestId = null): Payout
    {
        return Payout::fromModel($this->model($this->transport->send('/payments/cards/topup', $request, requestId: $requestId)));
    }

    /**
     * Pay out to a saved card token.
     */
    public function toToken(TokenPayoutRequest $request, ?string $requestId = null): Payout
    {
        return Payout::fromModel($this->model($this->transport->send('/payments/token/topup', $request, requestId: $requestId)));
    }
}
