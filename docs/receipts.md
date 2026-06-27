# Receipts (54-FZ)

Russian online cash registers (54-ФЗ) are served through CloudPayments'
**CloudKassir** fiscalization service. The SDK wraps the `/kkt/*` endpoints,
which — unlike the form-encoded payment endpoints — take a **JSON** request body.
You never build that JSON yourself: pass typed DTOs and the transport encodes the
whole envelope once.

```php
use CloudPayments\Client;

$client = Client::create('pk_xxxxxxxx', 'your_api_secret');
$receipts = $client->receipts(); // CloudPayments\Api\ReceiptsApi
```

## Creating a receipt

`ReceiptsApi::create()` registers a fiscal receipt at `/kkt/receipt`. Build a
`CustomerReceipt` from one or more `ReceiptItem`s plus a `TaxationSystem`, pick a
`ReceiptType`, and link the receipt to the payment it fiscalizes via `invoiceId`
and/or `accountId`. Provide `email` and/or `phone` so the customer receives the
electronic copy.

```php
use CloudPayments\Request\Receipt\CreateReceiptRequest;
use CloudPayments\Request\Receipt\CustomerReceipt;
use CloudPayments\Request\Receipt\ReceiptItem;
use CloudPayments\Enum\PaymentMethod;
use CloudPayments\Enum\PaymentObject;
use CloudPayments\Enum\ReceiptType;
use CloudPayments\Enum\TaxationSystem;
use CloudPayments\Enum\VatRate;

$customerReceipt = new CustomerReceipt(
    items: [
        new ReceiptItem(
            label: 'Кофе эспрессо',
            price: 150.0,
            quantity: 2.0,
            amount: 300.0,
            vat: VatRate::Vat20,
            method: PaymentMethod::FullPayment,
            object: PaymentObject::Commodity,
            measurementUnit: 'шт',
        ),
        new ReceiptItem(
            label: 'Доставка',
            price: 99.0,
            quantity: 1.0,
            amount: 99.0,
            vat: VatRate::Vat20,
            method: PaymentMethod::FullPayment,
            object: PaymentObject::Service,
        ),
    ],
    taxationSystem: TaxationSystem::SimplifiedIncome,
    email: 'customer@example.com',
    phone: '+79991234567',
);

$request = new CreateReceiptRequest(
    type: ReceiptType::Income,
    customerReceipt: $customerReceipt,
    inn: '7708806062',     // optional cashier/merchant INN override
    invoiceId: 'order-42', // links the receipt to your order
    accountId: 'user-17',  // links the receipt to a customer account
);

$receipt = $client->receipts()->create($request);

$receipt->id;          // CloudKassir receipt id
$receipt->fiscalSign;  // populated once fiscalized (may be null at first)
```

`type` and `customerReceipt` are required; `inn`, `invoiceId`, and `accountId`
are optional. `email`/`phone` live on the `CustomerReceipt`, not the request.

> Registration is asynchronous on CloudKassir's side. The `Receipt` returned by
> `create()` may not yet carry its fiscal identifiers (`fn`, `fiscalSign`,
> `deviceSn`, `ofd`, `url`) — poll `getStatus()` or wait for the
> [`Receipt` webhook](webhooks.md).

## Receipt line items

Each `ReceiptItem` describes one товарная позиция. Only the first four fields are
required; the rest refine the fiscal tags.

| Argument | Type | Required | Meaning |
|----------|------|----------|---------|
| `label` | `string` | yes | Item name printed on the receipt |
| `price` | `float` | yes | Unit price |
| `quantity` | `float` | yes | Number of units |
| `amount` | `float` | yes | Line total (`price × quantity`, after discounts) |
| `vat` | `?VatRate` | no | VAT rate; `null` means "no VAT" |
| `method` | `?PaymentMethod` | no | Payment method, tag 1214 |
| `object` | `?PaymentObject` | no | Payment subject, tag 1212 |
| `measurementUnit` | `?string` | no | Unit of measure, e.g. `'шт'`, `'кг'` |
| `ean13` | `?string` | no | Product barcode / marking code |
| `agentSign` | `?string` | no | Agent attribute for agent receipts |

## Splitting the total across payment forms

`ReceiptAmounts` breaks the receipt total down by how it is settled. All fields
are optional; pass it only when the payment is not a plain single electronic
settlement (e.g. partial advance, credit, or provision/bonus payment).

```php
use CloudPayments\Request\Receipt\CustomerReceipt;
use CloudPayments\Request\Receipt\ReceiptAmounts;
use CloudPayments\Enum\TaxationSystem;

$customerReceipt = new CustomerReceipt(
    items: $items,
    taxationSystem: TaxationSystem::General,
    email: 'customer@example.com',
    amounts: new ReceiptAmounts(
        electronic: 300.0,     // paid electronically now
        advancePayment: 99.0,  // covered by a prior advance
        credit: 0.0,           // sold on credit
        provision: 0.0,        // settled from provision/обеспечение
    ),
);
```

| Argument | Type | Meaning |
|----------|------|---------|
| `electronic` | `?float` | Amount paid electronically (безналичными) |
| `advancePayment` | `?float` | Amount covered by a prior advance/предоплата |
| `credit` | `?float` | Amount sold on credit |
| `provision` | `?float` | Amount settled from provision (встречное предоставление) |

## Status and retrieval

Both lookups take the CloudKassir receipt id and return a `Receipt`.

```php
$status = $client->receipts()->getStatus('receipt-id'); // /kkt/receipt/status/get
$receipt = $client->receipts()->get('receipt-id');       // /kkt/receipt/get
```

Use `getStatus()` to poll fiscalization progress after `create()`; use `get()`
to fetch the full registered document once it exists.

### `Receipt` properties

| Property | Type | Meaning |
|----------|------|---------|
| `id` | `?string` | CloudKassir receipt id |
| `documentNumber` | `?int` | Fiscal document number (ФД) |
| `sessionNumber` | `?int` | Shift/session number (смена) |
| `type` | `?string` | Operation type (`Income`, `IncomeReturn`, …) |
| `sum` | `?float` | Receipt total |
| `fn` | `?string` | Fiscal storage serial (ФН) |
| `fiscalSign` | `?string` | Fiscal sign / ФПД (tag 1077) |
| `deviceSn` | `?string` | Cash register serial number |
| `ofd` | `?string` | OFD (fiscal data operator) name |
| `url` | `?string` | Public URL of the fiscalized receipt |
| `invoiceId` | `?string` | Your order id, echoed back |
| `accountId` | `?string` | Your account id, echoed back |
| `amount` | `?float` | Receipt amount |
| `transactionId` | `?int` | Linked payment transaction id |
| `raw` | `array` | Untouched response Model, as an escape hatch |

Fiscal identifiers (`fn`, `fiscalSign`, `deviceSn`, `ofd`, `url`) populate only
once the receipt has been fiscalized — expect `null` immediately after `create()`.

## Enum reference

### `ReceiptType`

| Case | Value | Meaning |
|------|-------|---------|
| `Income` | `Income` | Приход — sale to customer |
| `IncomeReturn` | `IncomeReturn` | Возврат прихода — refund to customer |
| `Expense` | `Expense` | Расход — payout/expense |
| `ExpenseReturn` | `ExpenseReturn` | Возврат расхода |

### `TaxationSystem` (tag 1055)

| Case | Value | Meaning |
|------|-------|---------|
| `General` | `0` | Общая (ОСН) |
| `SimplifiedIncome` | `1` | УСН доход |
| `SimplifiedIncomeMinusExpense` | `2` | УСН доход минус расход |
| `Imputed` | `3` | ЕНВД |
| `AgriculturalTax` | `4` | ЕСХН |
| `Patent` | `5` | Патентная система (ПСН) |

### `VatRate`

`null` in a request means "no VAT"; otherwise use one of these cases.

| Case | Value | Meaning |
|------|-------|---------|
| `Vat0` | `0` | НДС 0% |
| `Vat10` | `10` | НДС 10% |
| `Vat20` | `20` | НДС 20% |
| `Vat10of110` | `110` | Расчётная ставка 10/110 |
| `Vat20of120` | `120` | Расчётная ставка 20/120 |

### `PaymentMethod` (tag 1214)

| Case | Value | Meaning |
|------|-------|---------|
| `FullPrepayment` | `1` | Предоплата 100% |
| `Prepayment` | `2` | Предоплата |
| `Advance` | `3` | Аванс |
| `FullPayment` | `4` | Полный расчёт |
| `PartialPayment` | `5` | Частичный расчёт и кредит |
| `Credit` | `6` | Передача в кредит |
| `CreditPayment` | `7` | Оплата кредита |

### `PaymentObject` (tag 1212)

| Case | Value | Meaning |
|------|-------|---------|
| `Commodity` | `1` | Товар |
| `Excise` | `2` | Подакцизный товар |
| `Job` | `3` | Работа |
| `Service` | `4` | Услуга |
| `GamblingBet` | `5` | Ставка азартной игры |
| `GamblingPrize` | `6` | Выигрыш азартной игры |
| `Lottery` | `7` | Лотерейный билет |
| `LotteryPrize` | `8` | Выигрыш лотереи |
| `IntellectualActivity` | `9` | Результаты интеллектуальной деятельности |
| `Payment` | `10` | Платёж |
| `AgentCommission` | `11` | Агентское вознаграждение |
| `Composite` | `12` | Составной предмет расчёта |
| `Another` | `13` | Иной предмет расчёта |
| `PropertyRight` | `14` | Имущественное право |
| `NonOperatingGain` | `15` | Внереализационный доход |
| `InsurancePremium` | `16` | Страховые взносы |
| `SalesTax` | `17` | Торговый сбор |
| `ResortFee` | `18` | Курортный сбор |
| `Deposit` | `19` | Залог |
| `Expense` | `20` | Расход |
| `PensionInsuranceIp` | `21` | Взносы на пенсионное страхование (ИП) |
| `PensionInsurance` | `22` | Взносы на пенсионное страхование |
| `MedicalInsuranceIp` | `23` | Взносы на медицинское страхование (ИП) |
| `MedicalInsurance` | `24` | Взносы на медицинское страхование |
| `SocialInsurance` | `25` | Взносы на социальное страхование |
| `CasinoPayment` | `26` | Платёж казино |

## Idempotency

`create()` accepts an optional last argument `?string $requestId`. Pin it so a
retry after a timeout re-registers the *same* receipt instead of duplicating it:

```php
$client->receipts()->create($request, 'receipt-order-42');
// retry with the SAME id → CloudPayments returns the original result
```

See [Idempotency (`X-Request-ID`)](configuration.md#idempotency-x-request-id) for
how ids are generated and deduplicated. `getStatus()` and `get()` are read-only
and take no request id.

## Webhooks

Once CloudKassir fiscalizes the receipt, CloudPayments delivers the registered
fiscal document to your endpoint via the **`Receipt`** notification — the
reliable way to learn the final `fiscalSign`, `fn`, and public `url` rather than
polling. See [Webhooks](webhooks.md).

## See also

- [Configuration](configuration.md) — credentials, gateways, idempotency
- [Payments](payments.md) — the charges these receipts fiscalize
- [Webhooks](webhooks.md) — receiving the registered `Receipt` document
- [Error handling](error-handling.md) — exceptions vs. declines
- [Enums reference](enums.md) — every enum and its cases
