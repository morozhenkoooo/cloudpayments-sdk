<?php

declare(strict_types=1);

namespace CloudPayments\Enum;

/**
 * 54-FZ payment subject (признак предмета расчёта), tag 1212.
 */
enum PaymentObject: int
{
    /** Товар. */
    case Commodity = 1;
    /** Подакцизный товар. */
    case Excise = 2;
    /** Работа. */
    case Job = 3;
    /** Услуга. */
    case Service = 4;
    /** Ставка азартной игры. */
    case GamblingBet = 5;
    /** Выигрыш азартной игры. */
    case GamblingPrize = 6;
    /** Лотерейный билет. */
    case Lottery = 7;
    /** Выигрыш лотереи. */
    case LotteryPrize = 8;
    /** Результаты интеллектуальной деятельности. */
    case IntellectualActivity = 9;
    /** Платёж. */
    case Payment = 10;
    /** Агентское вознаграждение. */
    case AgentCommission = 11;
    /** Составной предмет расчёта. */
    case Composite = 12;
    /** Иной предмет расчёта. */
    case Another = 13;
    /** Имущественное право. */
    case PropertyRight = 14;
    /** Внереализационный доход. */
    case NonOperatingGain = 15;
    /** Страховые взносы. */
    case InsurancePremium = 16;
    /** Торговый сбор. */
    case SalesTax = 17;
    /** Курортный сбор. */
    case ResortFee = 18;
    /** Залог. */
    case Deposit = 19;
    /** Расход. */
    case Expense = 20;
    /** Взносы на пенсионное страхование (ИП). */
    case PensionInsuranceIp = 21;
    /** Взносы на пенсионное страхование. */
    case PensionInsurance = 22;
    /** Взносы на медицинское страхование (ИП). */
    case MedicalInsuranceIp = 23;
    /** Взносы на медицинское страхование. */
    case MedicalInsurance = 24;
    /** Взносы на социальное страхование. */
    case SocialInsurance = 25;
    /** Платёж казино. */
    case CasinoPayment = 26;
}
