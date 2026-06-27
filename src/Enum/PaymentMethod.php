<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * 54-FZ payment method (признак способа расчёта), tag 1214.
 */
enum PaymentMethod: int
{
    /** Предоплата 100%. */
    case FullPrepayment = 1;
    /** Предоплата. */
    case Prepayment = 2;
    /** Аванс. */
    case Advance = 3;
    /** Полный расчёт. */
    case FullPayment = 4;
    /** Частичный расчёт и кредит. */
    case PartialPayment = 5;
    /** Передача в кредит. */
    case Credit = 6;
    /** Оплата кредита. */
    case CreditPayment = 7;
}
