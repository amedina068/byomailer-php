<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\WebhookDelivery;
use Byomailer\Data\WebhookEndpoint;
use Generator;

/**
 * The webhook endpoints of your client. Only an admin token can use them.
 */
final readonly class WebhookEndpoints extends Resource
{
    /**
     * One page of endpoints, newest first. The list does not include the
     * secrets.
     *
     * @return CursorPage<WebhookEndpoint>
     */
    public function list(?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get('webhook-endpoints', self::filled(['per_page' => $perPage, 'cursor' => $cursor]))->json();

        return CursorPage::fromBody($body, WebhookEndpoint::fromArray(...));
    }

    /**
     * @return Generator<int, WebhookEndpoint>
     */
    public function all(?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($perPage, $cursor));
    }

    public function find(int $endpointId): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->transport->get("webhook-endpoints/{$endpointId}")->data());
    }

    /**
     * Create an endpoint. The result has the secret. Store it now, because
     * the instance does not send it again.
     *
     * @param  string  $url  A public https URL.
     * @param  list<string>  $events  For example `['contact.subscribed', 'newsletter.sent']`.
     */
    public function create(string $url, array $events, ?string $description = null): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->transport->post('webhook-endpoints', self::filled([
            'url' => $url,
            'events' => $events,
            'description' => $description,
        ]))->data());
    }

    /**
     * Turn an endpoint on or off, or change its description. The URL and the
     * events cannot change. Create a new endpoint to change them.
     */
    public function update(int $endpointId, ?bool $active = null, ?string $description = null): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->transport->patch("webhook-endpoints/{$endpointId}", self::filled([
            'active' => $active,
            'description' => $description,
        ]), idempotent: true)->data());
    }

    public function delete(int $endpointId): void
    {
        $this->transport->delete("webhook-endpoints/{$endpointId}");
    }

    /**
     * One page of the deliveries of an endpoint, newest first.
     *
     * @return CursorPage<WebhookDelivery>
     */
    public function deliveries(int $endpointId, ?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get(
            "webhook-endpoints/{$endpointId}/deliveries",
            self::filled(['per_page' => $perPage, 'cursor' => $cursor]),
        )->json();

        return CursorPage::fromBody($body, WebhookDelivery::fromArray(...));
    }

    /**
     * @return Generator<int, WebhookDelivery>
     */
    public function allDeliveries(int $endpointId, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->deliveries($endpointId, $perPage, $cursor));
    }

    /**
     * Make a new secret. The old secret stops working at once. The result has
     * the new secret.
     */
    public function rotateSecret(int $endpointId): WebhookEndpoint
    {
        return WebhookEndpoint::fromArray($this->transport->post("webhook-endpoints/{$endpointId}/secret")->data());
    }

    /**
     * Queue a `webhook.test` delivery to the endpoint. The instance allows 10
     * requests a minute.
     */
    public function sendTest(int $endpointId): WebhookDelivery
    {
        return WebhookDelivery::fromArray($this->transport->post("webhook-endpoints/{$endpointId}/test")->data());
    }
}
