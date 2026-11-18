<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;

it('lists sequence summaries', function () {
    $history = [];

    $page = byomailer([json(page([[
        'id' => 3, 'name' => 'Welcome', 'status' => 'live', 'steps_count' => 4,
        'contacts_count' => 20, 'completed_count' => 5, 'active_count' => 15, 'project_ids' => [1],
    ]]))], $history)->sequences()->list(status: 'live', search: 'Wel', project: 1);

    $sequence = $page->first();

    expect(sent($history[0]['request']))->toBe('GET /api/v1/sequences?status=live&search=Wel&project=1')
        ->and($sequence?->isLive())->toBeTrue()
        ->and($sequence?->stepsCount)->toBe(4)
        ->and($sequence?->activeCount)->toBe(15)
        ->and($sequence?->steps)->toBe([])
        ->and($sequence?->createdAt)->toBeNull();
});

it('reads a sequence with its steps', function () {
    $sequence = byomailer([json(['data' => [
        'id' => 3, 'name' => 'Welcome', 'status' => 'paused', 'contacts_count' => 20, 'completed_count' => 5,
        'active_count' => 15, 'steps_count' => 1, 'steps' => [stepPayload()], 'segment_id' => null,
        'filters' => [], 'content_variables' => ['first_name' => 'friend'], 'created_at' => '2026-11-01T10:00:00.000000Z',
        'project_ids' => [],
    ]])])->sequences()->find(3);

    expect($sequence->steps[0]->subject())->toBe('Welcome')
        ->and($sequence->contentVariables)->toBe(['first_name' => 'friend'])
        ->and($sequence->projectIds)->toBe([]);
});

it('reads the statistics of a sequence', function () {
    $history = [];

    $stats = byomailer([json(['data' => [
        'id' => 3, 'name' => 'Welcome', 'status' => 'live',
        'stats' => ['contacts' => 20, 'unsubscribed' => 1, 'clicked' => 6],
        'mail_metrics' => ['total' => 40, 'sent' => 38, 'open_rate' => 41.2, 'delivery_rate' => null],
    ]])], $history)->sequences()->stats(3);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/sequences/3/stats')
        ->and($stats->name)->toBe('Welcome')
        ->and($stats->stats)->toBe(['contacts' => 20, 'unsubscribed' => 1, 'clicked' => 6])
        ->and($stats->mailMetrics)->toBe(['total' => 40, 'sent' => 38, 'open_rate' => 41.2, 'delivery_rate' => null]);
});

it('reads a step and its statistics', function () {
    $history = [];
    $client = byomailer([
        json(['data' => stepPayload()]),
        json(['data' => [
            'id' => 11, 'sequence_id' => 3, 'subject' => 'Welcome', 'delay_days' => 2, 'delay_type' => 'days', 'status' => 'live',
            'stats' => ['contacts' => 4, 'unsubscribed' => 0, 'clicked' => 1], 'mail_metrics' => ['total' => 4],
        ]]),
    ], $history);

    $step = $client->sequences()->step(3, 11);
    $stats = $client->sequences()->stepStats(3, 11);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/sequences/3/steps/11')
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/sequences/3/steps/11/stats')
        ->and($step->delayDays)->toBe(2)
        ->and($stats->sequenceId)->toBe(3)
        ->and($stats->subject)->toBe('Welcome');
});

it('replaces the filters of a step', function () {
    $history = [];
    $filters = [['category' => 'tags', 'mode' => 'none', 'values' => [5]]];

    $step = byomailer([json(['data' => stepPayload(['filters' => $filters])])], $history)->sequences()->replaceStepFilters(3, 11, $filters);

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/sequences/3/steps/11/filters')
        ->and(body($history[0]['request']))->toBe(['filters' => $filters])
        ->and($step->filters)->toBe($filters);
});

it('reads and replaces the content variables', function () {
    $history = [];
    $data = ['data' => ['sequence_id' => 3, 'detected_variables' => ['first_name'], 'content_variables' => ['first_name' => 'friend']]];

    $client = byomailer([json($data), json($data)], $history);

    expect($client->sequences()->variables(3)->defaults)->toBe(['first_name' => 'friend']);

    $client->sequences()->replaceVariables(3, ['first_name' => 'friend']);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/sequences/3/variables')
        ->and(sent($history[1]['request']))->toBe('PATCH /api/v1/sequences/3/variables')
        ->and(body($history[1]['request']))->toBe(['content_variables' => ['first_name' => 'friend']]);
});

it('exports the mails of a step as raw CSV text', function () {
    $history = [];
    $csv = "Email,Status\nana@example.com,sent\n";

    $result = byomailer([new Response(200, ['Content-Type' => 'text/csv; charset=UTF-8'], $csv)], $history)
        ->sequences()->exportStepMails(3, 11, [
            'status' => 'sent',
            'current_only' => true,
            'sent_after' => new DateTimeImmutable('2026-11-01T00:00:00+00:00'),
        ]);

    expect($result)->toBe($csv)
        ->and(sent($history[0]['request']))->toBe('GET /api/v1/sequences/3/steps/11/contact-mails/export?status=sent&current_only=1&sent_after=2026-11-01T00:00:00+00:00');
});
