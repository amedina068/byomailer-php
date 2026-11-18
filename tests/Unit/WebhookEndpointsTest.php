<?php

declare(strict_types=1);

it('lists and reads endpoints with no secret', function () {
    $history = [];
    $client = byomailer([json(page([endpointPayload()])), json(['data' => endpointPayload()])], $history);

    $endpoint = $client->webhookEndpoints()->list()->first();

    expect($endpoint?->events)->toBe(['contact.subscribed'])
        ->and($endpoint?->secret)->toBeNull()
        ->and($client->webhookEndpoints()->find(4)->active)->toBeTrue()
        ->and(sent($history[0]['request']))->toBe('GET /api/v1/webhook-endpoints')
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/webhook-endpoints/4');
});

it('creates an endpoint and returns the secret', function () {
    $history = [];

    $endpoint = byomailer([json(['data' => endpointPayload(['secret' => 'whsec_created'])], 201)], $history)
        ->webhookEndpoints()->create('https://example.com/webhooks/byomailer', ['contact.subscribed'], 'CRM sync');

    expect($endpoint->secret)->toBe('whsec_created')
        ->and(print_r($endpoint, true))->not->toContain('whsec_created')
        ->and(sent($history[0]['request']))->toBe('POST /api/v1/webhook-endpoints')
        ->and(body($history[0]['request']))->toBe([
            'url' => 'https://example.com/webhooks/byomailer',
            'events' => ['contact.subscribed'],
            'description' => 'CRM sync',
        ]);
});

it('turns an endpoint off', function () {
    $history = [];

    byomailer([json(['data' => endpointPayload(['active' => false, 'disabled_reason' => 'manual'])])], $history)
        ->webhookEndpoints()->update(4, active: false);

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/webhook-endpoints/4')
        ->and(body($history[0]['request']))->toBe(['active' => false]);
});

it('deletes an endpoint', function () {
    $history = [];

    byomailer([noContent()], $history)->webhookEndpoints()->delete(4);

    expect(sent($history[0]['request']))->toBe('DELETE /api/v1/webhook-endpoints/4');
});

it('lists the deliveries of an endpoint', function () {
    $history = [];

    $delivery = byomailer([json(page([deliveryPayload(['status' => 'success', 'response_status' => 200])]))], $history)
        ->webhookEndpoints()->deliveries(4, perPage: 10)->first();

    expect(sent($history[0]['request']))->toBe('GET /api/v1/webhook-endpoints/4/deliveries?per_page=10')
        ->and($delivery?->responseStatus)->toBe(200)
        ->and($delivery?->deliveredAt)->toBeNull();
});

it('rotates the secret', function () {
    $history = [];

    $endpoint = byomailer([json(['data' => endpointPayload(['secret' => 'whsec_rotated'])])], $history)->webhookEndpoints()->rotateSecret(4);

    expect($endpoint->secret)->toBe('whsec_rotated')
        ->and(sent($history[0]['request']))->toBe('POST /api/v1/webhook-endpoints/4/secret');
});

it('sends a test delivery', function () {
    $history = [];

    $delivery = byomailer([json(['data' => deliveryPayload()], 202)], $history)->webhookEndpoints()->sendTest(4);

    expect($delivery->eventType)->toBe('webhook.test')
        ->and($delivery->payload['message'])->toBe('This is a test delivery from BYOMailer.')
        ->and(sent($history[0]['request']))->toBe('POST /api/v1/webhook-endpoints/4/test');
});
