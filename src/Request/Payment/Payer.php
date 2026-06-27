<?php

declare(strict_types=1);

namespace CloudPayments\Request\Payment;

use CloudPayments\Contract\ApiRequest;

/**
 * Optional payer details attached to a payment (used for anti-fraud and some
 * acquiring scenarios).
 */
final readonly class Payer implements ApiRequest
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleName = null,
        public ?string $birth = null,
        public ?string $street = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $phone = null,
        public ?string $postcode = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'MiddleName' => $this->middleName,
            'Birth' => $this->birth,
            'Street' => $this->street,
            'Address' => $this->address,
            'City' => $this->city,
            'Country' => $this->country,
            'Phone' => $this->phone,
            'Postcode' => $this->postcode,
        ];
    }
}
