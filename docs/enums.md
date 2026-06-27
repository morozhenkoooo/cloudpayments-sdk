# Enums

Every enum lives in the `CloudPayments\Enum` namespace and is a native PHP backed
enum. The backing type (`string` or `int`) matches what the CloudPayments API
sends or expects on the wire, so you can pass `MyEnum::Case->value` to the API
and rebuild a case with `MyEnum::from()` / `MyEnum::tryFrom()`.

```php
use CloudPayments\Enum\Currency;

Currency::RUB->value;            // 'RUB'
Currency::tryFrom('EUR');        // Currency::EUR
Currency::tryFrom('XXX');        // null
```

## CheckResponseCode

Business response codes you return to CloudPayments from a `Check` webhook (sent
back as `{"code": N}`) to tell the gateway whether to proceed. Backed by `int`.

| Case | Value | Meaning |
|------|-------|---------|
| `Ok` | `0` | Approve — proceed with the payment. |
| `InvalidAccountId` | `11` | Invalid AccountId — the customer/account does not exist. |
| `CannotProcess` | `12` | Cannot process the payment right now (try again later). |
| `Rejected` | `13` | Reject the payment. |

## Currency

ISO 4217 currency codes supported by CloudPayments. Backed by `string`.

| Case | Value | Meaning |
|------|-------|---------|
| `RUB` | `RUB` | Russian ruble |
| `EUR` | `EUR` | Euro |
| `USD` | `USD` | US dollar |
| `GBP` | `GBP` | Pound sterling |
| `UAH` | `UAH` | Ukrainian hryvnia |
| `BYN` | `BYN` | Belarusian ruble |
| `BYR` | `BYR` | Belarusian ruble (old) |
| `KZT` | `KZT` | Kazakhstani tenge |
| `AZN` | `AZN` | Azerbaijani manat |
| `CHF` | `CHF` | Swiss franc |
| `CZK` | `CZK` | Czech koruna |
| `CAD` | `CAD` | Canadian dollar |
| `PLN` | `PLN` | Polish złoty |
| `SEK` | `SEK` | Swedish krona |
| `TRY` | `TRY` | Turkish lira |
| `CNY` | `CNY` | Chinese yuan |
| `INR` | `INR` | Indian rupee |
| `BRL` | `BRL` | Brazilian real |
| `ZAR` | `ZAR` | South African rand |
| `UZS` | `UZS` | Uzbekistani som |
| `BGN` | `BGN` | Bulgarian lev |
| `RON` | `RON` | Romanian leu |
| `AUD` | `AUD` | Australian dollar |
| `HKD` | `HKD` | Hong Kong dollar |
| `GEL` | `GEL` | Georgian lari |
| `KGS` | `KGS` | Kyrgyzstani som |
| `AMD` | `AMD` | Armenian dram |
| `JPY` | `JPY` | Japanese yen |
| `TJS` | `TJS` | Tajikistani somoni |
| `AED` | `AED` | UAE dirham |

## Interval

Recurring subscription billing interval unit. Backed by `string`.

| Case | Value | Meaning |
|------|-------|---------|
| `Day` | `Day` | Bill every N days |
| `Week` | `Week` | Bill every N weeks |
| `Month` | `Month` | Bill every N months |

## NotificationType

Inbound webhook notification kinds configured in the CloudPayments dashboard.
Backed by `string` (lowercase). See [Webhooks](webhooks.md).

| Case | Value | Meaning |
|------|-------|---------|
| `Check` | `check` | Pre-payment check — decide whether to allow the payment. |
| `Pay` | `pay` | Payment succeeded. |
| `Fail` | `fail` | Payment failed / declined. |
| `Confirm` | `confirm` | Two-stage payment was captured. |
| `Refund` | `refund` | Payment was refunded. |
| `Cancel` | `cancel` | Payment was cancelled / voided. |
| `Recurrent` | `recurrent` | Recurring subscription status changed. |
| `Receipt` | `receipt` | Fiscal receipt was registered. |

## PaymentMethod

54-FZ payment method (признак способа расчёта), tag 1214. Backed by `int`.

| Case | Value | Meaning |
|------|-------|---------|
| `FullPrepayment` | `1` | Предоплата 100%. |
| `Prepayment` | `2` | Предоплата. |
| `Advance` | `3` | Аванс. |
| `FullPayment` | `4` | Полный расчёт. |
| `PartialPayment` | `5` | Частичный расчёт и кредит. |
| `Credit` | `6` | Передача в кредит. |
| `CreditPayment` | `7` | Оплата кредита. |

## PaymentObject

54-FZ payment subject (признак предмета расчёта), tag 1212. Backed by `int`.

| Case | Value | Meaning |
|------|-------|---------|
| `Commodity` | `1` | Товар. |
| `Excise` | `2` | Подакцизный товар. |
| `Job` | `3` | Работа. |
| `Service` | `4` | Услуга. |
| `GamblingBet` | `5` | Ставка азартной игры. |
| `GamblingPrize` | `6` | Выигрыш азартной игры. |
| `Lottery` | `7` | Лотерейный билет. |
| `LotteryPrize` | `8` | Выигрыш лотереи. |
| `IntellectualActivity` | `9` | Результаты интеллектуальной деятельности. |
| `Payment` | `10` | Платёж. |
| `AgentCommission` | `11` | Агентское вознаграждение. |
| `Composite` | `12` | Составной предмет расчёта. |
| `Another` | `13` | Иной предмет расчёта. |
| `PropertyRight` | `14` | Имущественное право. |
| `NonOperatingGain` | `15` | Внереализационный доход. |
| `InsurancePremium` | `16` | Страховые взносы. |
| `SalesTax` | `17` | Торговый сбор. |
| `ResortFee` | `18` | Курортный сбор. |
| `Deposit` | `19` | Залог. |
| `Expense` | `20` | Расход. |
| `PensionInsuranceIp` | `21` | Взносы на пенсионное страхование (ИП). |
| `PensionInsurance` | `22` | Взносы на пенсионное страхование. |
| `MedicalInsuranceIp` | `23` | Взносы на медицинское страхование (ИП). |
| `MedicalInsurance` | `24` | Взносы на медицинское страхование. |
| `SocialInsurance` | `25` | Взносы на социальное страхование. |
| `CasinoPayment` | `26` | Платёж казино. |

## ReasonCode

Decline reason codes returned in `ReasonCode` for a declined transaction. Backed
by `int`. This enum is **non-exhaustive** — not every gateway code is listed — so
it is consumed via `ReasonCode::tryFrom($raw)`, and `Transaction` keeps the raw
integer alongside the mapped case (`reasonCode` is `null`, `reasonCodeRaw` holds
the int) whenever a code is not enumerated here. `label()` returns the case name.

| Case | Value | Meaning |
|------|-------|---------|
| `ReferToCardIssuer` | `5001` | Refer to card issuer. |
| `DoNotHonor` | `5005` | Do not honor. |
| `Error` | `5006` | Generic error. |
| `InvalidTransaction` | `5012` | Invalid transaction. |
| `AmountError` | `5013` | Invalid amount. |
| `InvalidCardNumber` | `5014` | Invalid card number. |
| `NoSuchIssuer` | `5015` | No such issuer. |
| `FormatError` | `5030` | Format error. |
| `BankNotSupportedBySwitch` | `5031` | Bank not supported by switch. |
| `ExpiredCardPickUp` | `5033` | Expired card, pick up. |
| `SuspectedFraud` | `5034` | Suspected fraud. |
| `RestrictedCardPickUp` | `5036` | Restricted card, pick up. |
| `LostCard` | `5041` | Lost card. |
| `StolenCard` | `5043` | Stolen card. |
| `InsufficientFunds` | `5051` | Insufficient funds. |
| `ExpiredCard` | `5054` | Expired card. |
| `TransactionNotPermitted` | `5057` | Transaction not permitted. |
| `TransactionNotPermittedToCardholder` | `5058` | Transaction not permitted to cardholder. |
| `ExceedWithdrawalAmountLimit` | `5061` | Exceeds withdrawal amount limit. |
| `RestrictedCard` | `5062` | Restricted card. |
| `SecurityViolation` | `5063` | Security violation. |
| `ExceedWithdrawalFrequency` | `5065` | Exceeds withdrawal frequency. |
| `IncorrectCvv` | `5082` | Incorrect CVV. |
| `CannotReachIssuer` | `5091` | Issuer unavailable. |
| `SystemError` | `5096` | System error. |
| `UnableToProcess` | `5097` | Unable to process. |
| `AuthenticationFailed` | `5204` | 3-D Secure authentication failed. |
| `AntiFraud` | `5206` | Blocked by anti-fraud rules. |

```php
use CloudPayments\Enum\ReasonCode;

$reason = ReasonCode::tryFrom($tx->reasonCodeRaw ?? 0); // ?ReasonCode (null if unmapped)
$reason?->label();                                       // e.g. 'InsufficientFunds'
```

## ReceiptType

54-FZ fiscal receipt operation type. Backed by `string`.

| Case | Value | Meaning |
|------|-------|---------|
| `Income` | `Income` | Приход — sale to customer. |
| `IncomeReturn` | `IncomeReturn` | Возврат прихода — refund to customer. |
| `Expense` | `Expense` | Расход — payout/expense. |
| `ExpenseReturn` | `ExpenseReturn` | Возврат расхода. |

## SubscriptionStatus

Lifecycle status of a recurring subscription. Backed by `string`.

| Case | Value | Meaning |
|------|-------|---------|
| `Active` | `Active` | Subscription is active and billing. |
| `PastDue` | `PastDue` | A charge failed; awaiting retry. |
| `Cancelled` | `Cancelled` | Cancelled by merchant or customer. |
| `Rejected` | `Rejected` | Rejected (could not be created/charged). |
| `Expired` | `Expired` | Reached its end and stopped. |

CloudPayments also exposes a numeric code for this status. The enum bridges both
representations:

- `code(): int` — the numeric code for a case (`Active` = 0, `PastDue` = 1,
  `Cancelled` = 2, `Rejected` = 3, `Expired` = 4).
- `fromCode(int $code): ?self` — the case for a numeric code, or `null`.
- `resolve(?string $status, ?int $code): ?self` — try the textual status first,
  then fall back to the numeric code.

## TaxationSystem

54-FZ taxation system (СНО) code, tag 1055. Backed by `int`.

| Case | Value | Meaning |
|------|-------|---------|
| `General` | `0` | Общая (ОСН). |
| `SimplifiedIncome` | `1` | Упрощённая, доход (УСН доход). |
| `SimplifiedIncomeMinusExpense` | `2` | Упрощённая, доход минус расход (УСН доход-расход). |
| `Imputed` | `3` | Единый налог на вменённый доход (ЕНВД). |
| `AgriculturalTax` | `4` | Единый сельскохозяйственный налог (ЕСХН). |
| `Patent` | `5` | Патентная система (ПСН). |

## TransactionStatus

Lifecycle status of a payment transaction. Backed by `string`. CloudPayments
reports both a textual `Status` and a numeric `StatusCode`; this enum bridges
them.

| Case | Value | Meaning |
|------|-------|---------|
| `AwaitingAuthentication` | `AwaitingAuthentication` | Awaiting 3-D Secure authentication. |
| `Authorized` | `Authorized` | Funds held (two-stage), not yet captured. |
| `Completed` | `Completed` | Captured / paid. |
| `Cancelled` | `Cancelled` | Voided / cancelled. |
| `Declined` | `Declined` | Declined — inspect `reasonCode`. |

Code-bridging helpers:

- `code(): int` — the numeric code for a case (`AwaitingAuthentication` = 1,
  `Authorized` = 2, `Completed` = 3, `Cancelled` = 4, `Declined` = 5).
- `fromCode(int $code): ?self` — the case for a numeric code, or `null`.
- `resolve(?string $status, ?int $code): ?self` — try the textual status first,
  then fall back to the numeric code. This is what `Transaction::fromModel()`
  uses to populate `status`.

A `Declined` status is a normal return value, not an exception — see
[Error handling › Declines are not exceptions](error-handling.md#declines-are-not-exceptions).

## VatRate

VAT rate (НДС) for a receipt line item. Backed by `int`. A `null` rate in a
request means "no VAT"; use these cases for the explicit rates CloudKassir
accepts.

| Case | Value | Meaning |
|------|-------|---------|
| `Vat0` | `0` | 0% VAT. |
| `Vat10` | `10` | 10% VAT. |
| `Vat20` | `20` | 20% VAT. |
| `Vat10of110` | `110` | Расчётная ставка 10/110. |
| `Vat20of120` | `120` | Расчётная ставка 20/120. |

## See also

- [Error handling](error-handling.md)
- [Payments](payments.md)
- [Subscriptions](subscriptions.md)
- [Configuration](configuration.md)
