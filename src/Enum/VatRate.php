<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * VAT rate (НДС) for a receipt line item. `null` in a request means "no VAT";
 * use these cases for the explicit rates CloudKassir accepts.
 */
enum VatRate: int
{
    case Vat0 = 0;
    case Vat10 = 10;
    case Vat20 = 20;
    /** Расчётная ставка 10/110. */
    case Vat10of110 = 110;
    /** Расчётная ставка 20/120. */
    case Vat20of120 = 120;
}
