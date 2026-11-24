<?php

declare(strict_types=1);

use Byomailer\Exceptions\MissingCredentialsException;
use Byomailer\Exceptions\WebhookVerificationException;
use Byomailer\Webhooks\VerificationFailure;
use Byomailer\Webhooks\Webhook;
use Byomailer\Webhooks\WebhookEvent;

/**
 * @return list<array<string, mixed>>
 */
function vectors(): array
{
    $vectors = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/webhook-signature-vectors.json'), true, flags: JSON_THROW_ON_ERROR);

    return $vectors['cases'];
}

/**
 * The headers of a vector. A null value leaves the header out.
 *
 * @param  array<string, mixed>  $case
 * @return array<string, string>
 */
function vectorHeaders(array $case): array
{
    return array_filter([
        'X-Byomailer-Signature' => $case['signature'],
        'X-Byomailer-Timestamp' => $case['timestamp'],
        'X-Byomailer-Event' => 'contact.subscribed',
        'X-Byomailer-Delivery' => $case['delivery'],
    ], fn (?string $value) => $value !== null);
}

$secret = 'whsec_test0123456789abcdefghijklmnopqrstuvwxyz';

it('matches each shared test vector', function (array $case) {
    $webhook = new Webhook($case['secret']);

    if ($case['valid']) {
        $event = $webhook->verify($case['body'], vectorHeaders($case), now: $case['now']);

        $payload = json_decode($case['body'], true);

        expect($event->type)->toBe($payload['event'])
            ->and($event->payload)->toBe($payload)
            ->and($event->timestamp)->toBe((int) $case['timestamp'])
            ->and($event->deliveryId)->toBe($case['delivery_id']);

        return;
    }

    try {
        $webhook->verify($case['body'], vectorHeaders($case), now: $case['now']);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason->value)->toBe($case['reason']);

        return;
    }

    test()->fail("The case [{$case['name']}] was accepted.");
})->with(fn () => array_combine(array_column(vectors(), 'name'), array_map(fn (array $case) => [$case], vectors())));

it('returns the decoded event of a valid delivery', function () use ($secret) {
    $case = vectors()[1];

    $event = (new Webhook($secret))->verify($case['body'], vectorHeaders($case), now: $case['now']);

    expect($event)->toBeInstanceOf(WebhookEvent::class)
        ->and($event->type)->toBe(WebhookEvent::CONTACT_SUBSCRIBED)
        ->and($event->is('contact.subscribed'))->toBeTrue()
        ->and($event->deliveryId)->toBe($case['delivery'])
        ->and($event->occurredAt?->format(DATE_ATOM))->toBe('2025-11-18T10:00:00+00:00')
        ->and($event->contact()['company'])->toBe('Café Ñandú')
        ->and($event->contact()['name'])->toBe('Ana Pérez 🎉')
        ->and($event->toArray())->toBe($event->payload);
});

it('reads the headers in any case and as lists', function () use ($secret) {
    $case = vectors()[0];

    $event = (new Webhook($secret))->verify($case['body'], [
        'x-byomailer-signature' => [$case['signature']],
        'X-BYOMAILER-TIMESTAMP' => [$case['timestamp']],
    ], now: $case['now']);

    expect($event->type)->toBe(WebhookEvent::TEST)
        ->and($event->contact())->toBeNull();
});

it('accepts an event type that it does not know', function () use ($secret) {
    $body = '{"event":"contact.deleted","occurred_at":"2026-11-18T10:00:00+00:00"}';
    $timestamp = '1763460000';

    $event = (new Webhook($secret))->verify($body, [
        'X-Byomailer-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        'X-Byomailer-Timestamp' => $timestamp,
    ], now: 1763460000);

    expect($event->type)->toBe('contact.deleted');
});

it('takes the type from the event header when the payload has no event', function () use ($secret) {
    $body = '{"occurred_at":"2026-11-18T10:00:00+00:00"}';
    $timestamp = '1763460000';

    $event = (new Webhook($secret))->verify($body, [
        'X-Byomailer-Event' => 'contact.deleted',
        'X-Byomailer-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        'X-Byomailer-Timestamp' => $timestamp,
    ], now: 1763460000);

    expect($event->type)->toBe('contact.deleted')
        ->and($event->deliveryId)->toBeNull();
});

it('gives a null delivery ID for an empty delivery header', function () use ($secret) {
    $case = vectors()[0];

    $event = (new Webhook($secret))->verify($case['body'], [...vectorHeaders($case), 'X-Byomailer-Delivery' => ''], now: $case['now']);

    expect($event->deliveryId)->toBeNull();
});

it('rejects a delivery with no timestamp header with the timestamp reason', function () use ($secret) {
    try {
        (new Webhook($secret))->verify('{}', ['X-Byomailer-Signature' => 'sha256='.str_repeat('0', 64)]);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Timestamp);

        return;
    }

    test()->fail('The delivery was accepted.');
});

it('rejects a delivery with no signature header', function () use ($secret) {
    try {
        (new Webhook($secret))->verify('{}', ['X-Byomailer-Timestamp' => (string) time()]);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Signature);

        return;
    }

    test()->fail('The delivery was accepted.');
});

it('rejects a body that was decoded and encoded again', function () use ($secret) {
    $case = vectors()[1];
    $encodedAgain = json_encode(json_decode($case['body'], true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    expect(fn () => (new Webhook($secret))->verify($encodedAgain, vectorHeaders($case), now: $case['now']))
        ->toThrow(WebhookVerificationException::class);
});

it('lets the caller change the tolerance', function () use ($secret) {
    $case = vectors()[0];

    expect(fn () => (new Webhook($secret, tolerance: 10))->verify($case['body'], vectorHeaders($case), now: $case['now']))
        ->toThrow(WebhookVerificationException::class, '10 seconds');

    expect((new Webhook($secret, tolerance: 600))->verify($case['body'], vectorHeaders($case), now: (int) $case['timestamp'] + 500)->type)
        ->toBe('webhook.test');
});

it('uses the clock when no time is given', function () use ($secret) {
    $body = '{"event":"webhook.test","occurred_at":"2026-11-18T10:00:00+00:00"}';
    $timestamp = (string) time();

    $event = (new Webhook($secret))->verify($body, [
        'X-Byomailer-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        'X-Byomailer-Timestamp' => $timestamp,
    ]);

    expect($event->type)->toBe('webhook.test');
});

it('rejects a signed body that is not a JSON object', function () use ($secret) {
    $timestamp = '1763460000';

    try {
        (new Webhook($secret))->verify('not json', [
            'X-Byomailer-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.not json', $secret),
            'X-Byomailer-Timestamp' => $timestamp,
        ], now: 1763460000);
    } catch (WebhookVerificationException $exception) {
        expect($exception->reason)->toBe(VerificationFailure::Payload);

        return;
    }

    test()->fail('The delivery was accepted.');
});

it('needs a secret', function () {
    new Webhook('');
})->throws(MissingCredentialsException::class, 'BYOMAILER_WEBHOOK_SECRET');

it('keeps the secret out of debug output', function () use ($secret) {
    expect(print_r(new Webhook($secret), true))->not->toContain($secret);
});
