<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * 54-FZ fiscal receipt operation type.
 */
enum ReceiptType: string
{
    /** Приход — sale to customer. */
    case Income = 'Income';
    /** Возврат прихода — refund to customer. */
    case IncomeReturn = 'IncomeReturn';
    /** Расход — payout/expense. */
    case Expense = 'Expense';
    /** Возврат расхода. */
    case ExpenseReturn = 'ExpenseReturn';
}
