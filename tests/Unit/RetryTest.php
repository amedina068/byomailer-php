<?php

declare(strict_types=1);

use Byomailer\Exceptions\ConnectionException;
use Byomailer\Exceptions\NotFoundException;
use Byomailer\Exceptions\RateLimitException;
use Byomailer\Exceptions\ServerException;
use Byomailer\Http\Response;
use Byomailer\Http\RetryPolicy;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

function connectionRefused(): ConnectException
{
    return new ConnectException('cURL error 7: Failed to connect: Connection refused', new Request('GET', 'https://mail.example.com'));
}

function readTimeout(): ConnectException
{
    return new ConnectException('cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received', new Request('GET', 'https://mail.example.com'));
}

it('retries a GET after a server error', function () {
    $history = [];
    $delays = [];
    $client = byomailer([json([], 500), json([], 503), json(['data' => []])], $history, noSleep(2, $delays));

    expect($client->tags()->list())->toBe([])
        ->and($history)->toHaveCount(3)
        ->and($delays)->toHaveCount(2);
});

it('throws the last error when the retries are used up', function () {
    $history = [];
    $client = byomailer([json([], 500), json([], 500), json([], 500)], $history, noSleep(2));

    expect(fn () => $client->tags()->list())->toThrow(ServerException::class)
        ->and($history)->toHaveCount(3);
});

it('does not retry when retries are off', function () {
    $history = [];
    $client = byomailer([json([], 500), json(['data' => []])], $history, 0);

    expect(fn () => $client->tags()->list())->toThrow(ServerException::class)
        ->and($history)->toHaveCount(1);
});

it('does not retry a POST that is not idempotent after a server error', function (Closure $call) {
    $history = [];
    $client = byomailer([json([], 500), json(['data' => []], 201)], $history, noSleep(2));

    expect(fn () => $call($client))->toThrow(ServerException::class)
        ->and($history)->toHaveCount(1);
})->with([
    'add a contact' => [fn ($client) => $client->contacts()->addToList(3, 'ana@example.com')],
    'create a tag' => [fn ($client) => $client->tags()->create('vip')],
    'send a test email' => [fn ($client) => $client->newsletters()->sendTest(7)],
    'send a test delivery' => [fn ($client) => $client->webhookEndpoints()->sendTest(4)],
    'rotate a secret' => [fn ($client) => $client->webhookEndpoints()->rotateSecret(4)],
    'update a newsletter' => [fn ($client) => $client->newsletters()->update(7, ['title' => 'x'])],
]);

it('retries an idempotent write after a server error', function (Closure $call, array $response) {
    $history = [];
    $client = byomailer([json([], 502), json($response)], $history, noSleep(2));

    $call($client);

    expect($history)->toHaveCount(2);
})->with([
    'rename a list' => [fn ($client) => $client->lists()->rename(3, 'Buyers'), ['data' => listPayload()]],
    'update a contact' => [fn ($client) => $client->contacts()->update(42, name: 'Ana'), ['data' => contactPayload()]],
    'count recipients' => [fn ($client) => $client->newsletters()->countRecipients(), ['count' => 1]],
    'replace step filters' => [fn ($client) => $client->sequences()->replaceStepFilters(3, 11, []), ['data' => stepPayload()]],
    'delete a tag' => [fn ($client) => $client->tags()->delete(5), []],
]);

it('retries a POST after a rate limit and waits for Retry-After', function () {
    $history = [];
    $delays = [];
    $client = byomailer([
        json(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '3']),
        json(['message' => 'Test email sent to owner@example.com', 'sent_to' => 'owner@example.com']),
    ], $history, noSleep(2, $delays));

    expect($client->newsletters()->sendTest(7))->toBe('owner@example.com')
        ->and($history)->toHaveCount(2)
        ->and($delays)->toBe([3000]);
});

it('does not wait when Retry-After is longer than the limit', function () {
    $history = [];
    $delays = [];
    $client = byomailer([json([], 429, ['Retry-After' => '120']), json(['data' => []])], $history, noSleep(2, $delays, maxRetryAfter: 60));

    expect(fn () => $client->tags()->list())->toThrow(RateLimitException::class)
        ->and($history)->toHaveCount(1)
        ->and($delays)->toBe([]);
});

it('retries a POST after a connection error before the request was sent', function () {
    $history = [];
    $client = byomailer([connectionRefused(), json(['data' => tagPayload()], 201)], $history, noSleep(2));

    expect($client->tags()->create('vip')->name)->toBe('vip')
        ->and($history)->toHaveCount(2);
});

it('does not retry a POST after a timeout, because the instance can have received it', function () {
    $history = [];
    $client = byomailer([readTimeout(), json(['data' => tagPayload()], 201)], $history, noSleep(2));

    expect(fn () => $client->tags()->create('vip'))->toThrow(ConnectionException::class)
        ->and($history)->toHaveCount(1);
});

it('retries a GET after a timeout', function () {
    $history = [];
    $client = byomailer([readTimeout(), json(['data' => []])], $history, noSleep(2));

    expect($client->tags()->list())->toBe([])
        ->and($history)->toHaveCount(2);
});

it('does not retry a client error', function () {
    $history = [];
    $client = byomailer([json(['message' => 'Not found.'], 404), json(['data' => []])], $history, noSleep(2));

    expect(fn () => $client->tags()->list())->toThrow(NotFoundException::class)
        ->and($history)->toHaveCount(1);
});

it('grows the delay exponentially with jitter', function () {
    $policy = new RetryPolicy(retries: 5, baseDelayMs: 100, maxDelayMs: 1000);

    foreach ([1 => 100, 2 => 200, 3 => 400, 4 => 800, 5 => 1000] as $attempt => $ceiling) {
        for ($i = 0; $i < 20; $i++) {
            expect($policy->delay($attempt))->toBeGreaterThanOrEqual(intdiv($ceiling, 2))->toBeLessThanOrEqual($ceiling);
        }
    }
});

it('uses Retry-After in place of the computed delay', function () {
    expect((new RetryPolicy)->delay(1, 7))->toBe(7000);
});

it('reads Retry-After as seconds or as an HTTP date', function () {
    $date = gmdate('D, d M Y H:i:s \G\M\T', time() + 30);

    expect((new Response(429, '', ['retry-after' => ['5']]))->retryAfter())->toBe(5)
        ->and((new Response(429, '', ['retry-after' => [$date]]))->retryAfter())->toBeGreaterThanOrEqual(28)->toBeLessThanOrEqual(30)
        ->and((new Response(429, '', ['retry-after' => ['soon']]))->retryAfter())->toBeNull()
        ->and((new Response(429, ''))->retryAfter())->toBeNull();
});

it('has no retries with the none policy', function () {
    expect(RetryPolicy::none()->allows(1))->toBeFalse();
});

it('sleeps for real when no sleep callback is given', function () {
    $start = microtime(true);

    (new RetryPolicy)->sleep(20);

    expect(microtime(true) - $start)->toBeGreaterThanOrEqual(0.015);
});
