<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Business response codes returned to CloudPayments from a `Check` webhook
 * (sent back as `{"code": N}`). They tell the gateway whether to proceed.
 */
enum CheckResponseCode: int
{
    /** Approve — proceed with the payment. */
    case Ok = 0;
    /** Invalid AccountId — the customer/account does not exist. */
    case InvalidAccountId = 11;
    /** Cannot process the payment right now (try again later). */
    case CannotProcess = 12;
    /** Reject the payment. */
    case Rejected = 13;
}
