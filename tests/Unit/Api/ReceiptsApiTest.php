<?php

declare(strict_types=1);

namespace CloudPayments\Tests\Unit\Api;

use CloudPayments\Api\ReceiptsApi;
use CloudPayments\Enum\ReceiptType;
use CloudPayments\Enum\TaxationSystem;
use CloudPayments\Enum\VatRate;
use CloudPayments\Request\Receipt\CreateReceiptRequest;
use CloudPayments\Request\Receipt\CustomerReceipt;
use CloudPayments\Request\Receipt\ReceiptItem;
use CloudPayments\Tests\Support\MockHttp;
use PHPUnit\Framework\TestCase;

final class ReceiptsApiTest extends TestCase
{
    private MockHttp $http;
    private ReceiptsApi $api;

    protected function setUp(): void
    {
        $this->http = new MockHttp();
        $this->api = new ReceiptsApi($this->http->transport);
    }

    public function testCreateSendsJsonWithCorrectCasing(): void
    {
        $this->http->queueModel(['Id' => 'rc_1']);

        $receipt = $this->api->create(new CreateReceiptRequest(
            type: ReceiptType::Income,
            customerReceipt: new CustomerReceipt(
                items: [new ReceiptItem('Pro plan', price: 990.0, quantity: 1.0, amount: 990.0, vat: VatRate::Vat20)],
                taxationSystem: TaxationSystem::SimplifiedIncome,
                email: 'buyer@example.com',
            ),
            invoiceId: 'ORDER-42',
        ));

        self::assertSame('rc_1', $receipt->id);

        $request = $this->http->lastRequest();
        self::assertSame('https://api.cloudpayments.ru/kkt/receipt', (string) $request->getUri());
        self::assertStringContainsString('application/json', $request->getHeaderLine('Content-Type'));

        $body = json_decode($this->http->lastRequestBody(), true);
        self::assertIsArray($body);

        // Top-level keys are PascalCase
        self::assertSame('Income', $body['Type']);
        self::assertSame('ORDER-42', $body['InvoiceId']);
        self::assertArrayHasKey('CustomerReceipt', $body);

        // CustomerReceipt and items are camelCase
        $cr = $body['CustomerReceipt'];
        self::assertIsArray($cr);
        self::assertSame(1, $cr['taxationSystem']);
        self::assertSame('buyer@example.com', $cr['email']);
        self::assertIsArray($cr['items']);
        self::assertIsArray($cr['items'][0]);
        self::assertSame('Pro plan', $cr['items'][0]['label']);
        self::assertSame(20, $cr['items'][0]['vat']);
        // CultureName is NOT injected into JSON bodies
        self::assertArrayNotHasKey('CultureName', $body);
    }

    public function testGetStatus(): void
    {
        $this->http->queueModel(['Id' => 'rc_1', 'Url' => 'https://ofd/r/1']);

        $receipt = $this->api->getStatus('rc_1');

        self::assertSame('https://ofd/r/1', $receipt->url);
        self::assertSame('https://api.cloudpayments.ru/kkt/receipt/status/get', (string) $this->http->lastRequest()->getUri());
    }
}
