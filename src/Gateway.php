<?php

declare(strict_types=1);

namespace CloudPayments;

/**
 * CloudPayments API gateway endpoints.
 *
 * Choose the gateway that matches the country your CloudPayments account is
 * registered in. Both speak the identical API; only the host differs.
 */
enum Gateway: string
{
    case Russia = 'https://api.cloudpayments.ru';
    case Kazakhstan = 'https://api.cloudpayments.kz';

    public function baseUrl(): string
    {
        return $this->value;
    }
}
