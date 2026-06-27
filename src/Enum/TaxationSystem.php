<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * 54-FZ taxation system (СНО) code, tag 1055.
 */
enum TaxationSystem: int
{
    /** Общая (ОСН). */
    case General = 0;
    /** Упрощённая, доход (УСН доход). */
    case SimplifiedIncome = 1;
    /** Упрощённая, доход минус расход (УСН доход-расход). */
    case SimplifiedIncomeMinusExpense = 2;
    /** Единый налог на вменённый доход (ЕНВД). */
    case Imputed = 3;
    /** Единый сельскохозяйственный налог (ЕСХН). */
    case AgriculturalTax = 4;
    /** Патентная система (ПСН). */
    case Patent = 5;
}
