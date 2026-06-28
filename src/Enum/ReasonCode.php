<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * Decline reason codes returned in `ReasonCode` for a declined transaction.
 *
 * Not every gateway code is enumerated; use {@see ReasonCode::tryFrom()} and
 * keep the raw integer alongside for codes not listed here.
 */
enum ReasonCode: int
{
    case ReferToCardIssuer = 5001;
    case DoNotHonor = 5005;
    case Error = 5006;
    case InvalidTransaction = 5012;
    case AmountError = 5013;
    case InvalidCardNumber = 5014;
    case NoSuchIssuer = 5015;
    case FormatError = 5030;
    case BankNotSupportedBySwitch = 5031;
    case ExpiredCardPickUp = 5033;
    case SuspectedFraud = 5034;
    case RestrictedCardPickUp = 5036;
    case LostCard = 5041;
    case StolenCard = 5043;
    case InsufficientFunds = 5051;
    case ExpiredCard = 5054;
    case TransactionNotPermitted = 5057;
    case TransactionNotPermittedToCardholder = 5058;
    case ExceedWithdrawalAmountLimit = 5061;
    case RestrictedCard = 5062;
    case SecurityViolation = 5063;
    case ExceedWithdrawalFrequency = 5065;
    case IncorrectCvv = 5082;
    case CannotReachIssuer = 5091;
    case SystemError = 5096;
    case UnableToProcess = 5097;
    case AuthenticationFailed = 5204;
    case AntiFraud = 5206;

    // 3xxx — the payment was rejected by the merchant's own Check/Pay
    // notification (webhook), not by the acquirer. CloudPayments does not
    // publish the full numeric list; only the codes confirmed against the live
    // API are enumerated here — others surface via `reasonCodeRaw`.

    /** The merchant Check notification rejected the payment: AccountId is invalid/unknown. */
    case CheckResponseInvalidAccountId = 3002;

    public function label(): string
    {
        return $this->name;
    }
}
