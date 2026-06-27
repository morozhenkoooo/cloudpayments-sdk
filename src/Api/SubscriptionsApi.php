<?php

declare(strict_types=1);

namespace CloudPayments\Api;

use CloudPayments\Request\Subscription\CreateSubscriptionRequest;
use CloudPayments\Request\Subscription\UpdateSubscriptionRequest;
use CloudPayments\Response\Subscription;

/**
 * Subscriptions API: create, fetch, list, update, and cancel recurring
 * subscriptions billed against saved card tokens.
 */
final class SubscriptionsApi extends AbstractApi
{
    /**
     * Create a recurring subscription.
     */
    public function create(CreateSubscriptionRequest $request, ?string $requestId = null): Subscription
    {
        return Subscription::fromModel($this->model($this->transport->send('/subscriptions/create', $request, requestId: $requestId)));
    }

    /**
     * Fetch a subscription by its id.
     */
    public function get(string $id): Subscription
    {
        return Subscription::fromModel($this->model($this->transport->send('/subscriptions/get', ['Id' => $id])));
    }

    /**
     * List all subscriptions for a merchant AccountId.
     *
     * @return list<Subscription>
     */
    public function findByAccountId(string $accountId): array
    {
        $envelope = $this->transport->send('/subscriptions/find', ['accountId' => $accountId]);

        $this->ensureSuccess($envelope);

        $subscriptions = [];

        foreach ($envelope->model as $row) {
            if (\is_array($row)) {
                $subscriptions[] = Subscription::fromModel($row);
            }
        }

        return $subscriptions;
    }

    /**
     * Update an existing subscription.
     */
    public function update(UpdateSubscriptionRequest $request, ?string $requestId = null): Subscription
    {
        return Subscription::fromModel($this->model($this->transport->send('/subscriptions/update', $request, requestId: $requestId)));
    }

    /**
     * Cancel a subscription by its id.
     */
    public function cancel(string $id, ?string $requestId = null): void
    {
        $this->ensureSuccess($this->transport->send('/subscriptions/cancel', ['Id' => $id], requestId: $requestId));
    }
}
