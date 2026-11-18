<?php

declare(strict_types=1);

use Byomailer\Byomailer;
use Byomailer\Http\RetryPolicy;
use Byomailer\Tests\Laravel\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Psr\Http\Message\RequestInterface;

uses(TestCase::class)->in('Laravel');

/**
 * A client that answers from a queue of responses and records each request.
 *
 * @param  list<PsrResponse|Throwable>  $queue
 * @param  array<int, array{request: RequestInterface}>  $history
 */
function byomailer(array $queue = [], array &$history = [], int|RetryPolicy $retries = 0): Byomailer
{
    $stack = HandlerStack::create(new MockHandler($queue));
    $stack->push(Middleware::history($history));

    return new Byomailer(
        url: 'https://mail.example.com',
        token: 'test-token',
        retries: $retries,
        httpClient: new GuzzleClient(['handler' => $stack]),
    );
}

/**
 * @param  array<array-key, mixed>|null  $body
 * @param  array<string, string>  $headers
 */
function json(?array $body, int $status = 200, array $headers = []): PsrResponse
{
    return new PsrResponse($status, ['Content-Type' => 'application/json'] + $headers, json_encode($body, JSON_THROW_ON_ERROR));
}

function noContent(): PsrResponse
{
    return new PsrResponse(204);
}

/**
 * The decoded JSON body of a recorded request.
 *
 * @return array<array-key, mixed>
 */
function body(RequestInterface $request): array
{
    return json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * The method and the path with the query of a recorded request.
 */
function sent(RequestInterface $request): string
{
    $uri = $request->getUri();
    $query = $uri->getQuery();

    return $request->getMethod().' '.$uri->getPath().($query === '' ? '' : '?'.urldecode($query));
}

/**
 * A retry policy that records each delay and does not sleep.
 *
 * @param  list<int>  $delays
 */
function noSleep(int $retries, array &$delays = [], int $maxRetryAfter = 60): RetryPolicy
{
    return new RetryPolicy(
        retries: $retries,
        maxRetryAfter: $maxRetryAfter,
        sleep: function (int $milliseconds) use (&$delays): void {
            $delays[] = $milliseconds;
        },
    );
}

/**
 * @param  list<array<string, mixed>>  $items
 * @return array<string, mixed>
 */
function page(array $items, ?string $next = null, ?string $previous = null): array
{
    return [
        'data' => $items,
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['path' => 'https://mail.example.com/api/v1/x', 'per_page' => 25, 'next_cursor' => $next, 'prev_cursor' => $previous],
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 42,
        'uuid' => '9b2f6c1e-4a7d-4f3b-8c55-0d6e2a1b3c4d',
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'extra_attributes' => ['company' => 'Acme'],
        'unsubscribed_at' => null,
        'unsubscribed_by_contact' => false,
        'tags' => [tagPayload()],
        'lists' => [listPayload()],
        'topics' => [topicPayload()],
        'sequences' => [['id' => 3, 'name' => 'Welcome', 'status' => 'live']],
        'created_at' => '2026-11-01T10:00:00.000000Z',
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function tagPayload(array $overrides = []): array
{
    return $overrides + ['id' => 5, 'uuid' => 'a1b2c3d4-0000-4000-8000-000000000005', 'name' => 'vip', 'created_at' => '2026-11-01T10:00:00.000000Z'];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function listPayload(array $overrides = []): array
{
    return $overrides + ['id' => 3, 'uuid' => 'a1b2c3d4-0000-4000-8000-000000000003', 'name' => 'Customers', 'opt_in_enabled' => false, 'created_at' => '2026-11-01T10:00:00.000000Z'];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function topicPayload(array $overrides = []): array
{
    return $overrides + ['id' => 8, 'uuid' => 'a1b2c3d4-0000-4000-8000-000000000008', 'list_id' => 3, 'name' => 'Product news', 'description' => null, 'created_at' => '2026-11-01T10:00:00.000000Z'];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function identityPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 2,
        'uuid' => 'a1b2c3d4-0000-4000-8000-000000000002',
        'email' => 'news@example.com',
        'name' => 'Example News',
        'domain' => 'example.com',
        'identity_status' => 'verified',
        'verified_at' => '2026-10-01T10:00:00.000000Z',
        'created_at' => '2026-10-01T09:00:00.000000Z',
        'updated_at' => '2026-10-01T10:00:00.000000Z',
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newsletterPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 7,
        'uuid' => '1c0d8e2f-3b4a-4c5d-9e6f-7a8b9c0d1e2f',
        'title' => 'November news',
        'status' => 'draft',
        'subject' => 'Our November news',
        'preview_text' => null,
        'email_identity_id' => 2,
        'email_identity' => identityPayload(),
        'email_template_id' => null,
        'from_email' => 'news@example.com',
        'from_name' => 'Example News',
        'scheduled_at' => null,
        'sent_at' => null,
        'segment_id' => null,
        'filters' => [['category' => 'lists', 'mode' => 'any', 'values' => [3]]],
        'content_variables' => ['first_name' => 'friend'],
        'ab_test_enabled' => false,
        'ab_test_winner_variant' => null,
        'subject_b' => null,
        'preview_text_b' => null,
        'total_contacts_reached' => 0,
        'project_ids' => [1],
        'metrics' => ['total' => 10, 'sent' => 8, 'pending' => 2, 'opened' => 4, 'bounced' => 0, 'unsubscribed' => 1, 'complained' => 0, 'open_rate' => 50, 'unsubscribe_rate' => 12.5],
        'created_at' => '2026-11-01T10:00:00.000000Z',
        'updated_at' => '2026-11-01T10:00:00.000000Z',
        'html_content' => '<p>Hello {{ first_name }}</p>',
        'text_content' => 'Hello {{ first_name }}',
        'html_content_b' => null,
        'text_content_b' => null,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stepPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 11,
        'sequence_id' => 3,
        'delay_days' => 2,
        'delay_type' => 'days',
        'status' => 'live',
        'filters' => [],
        'message' => ['id' => 90, 'subject' => 'Welcome', 'html_content' => '<p>Hi</p>'],
        'created_at' => '2026-11-01T10:00:00.000000Z',
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function endpointPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 4,
        'uuid' => 'a1b2c3d4-0000-4000-8000-000000000004',
        'url' => 'https://example.com/webhooks/byomailer',
        'description' => 'CRM sync',
        'events' => ['contact.subscribed'],
        'active' => true,
        'disabled_reason' => null,
        'consecutive_failures' => 0,
        'last_triggered_at' => null,
        'disabled_at' => null,
        'created_at' => '2026-11-01T10:00:00+00:00',
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function deliveryPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 30,
        'uuid' => 'a1b2c3d4-0000-4000-8000-000000000030',
        'event_type' => 'webhook.test',
        'status' => 'pending',
        'attempts' => 0,
        'response_status' => null,
        'response_body' => null,
        'error' => null,
        'payload' => ['event' => 'webhook.test', 'occurred_at' => '2026-11-01T10:00:00+00:00', 'message' => 'This is a test delivery from BYOMailer.'],
        'dispatched_at' => '2026-11-01T10:00:00+00:00',
        'delivered_at' => null,
        'created_at' => '2026-11-01T10:00:00+00:00',
    ];
}
