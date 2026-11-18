<?php

declare(strict_types=1);

$block = ['id' => 1, 'uuid' => 'a1b2c3d4-0000-4000-8000-000000000001', 'name' => 'footer', 'html_content' => '<p>Bye</p>', 'created_at' => null, 'updated_at' => null];
$template = ['id' => 6, 'name' => 'Brand', 'html_content' => '<html>{{ content }}</html>', 'is_default' => true, 'archived_at' => null, 'created_at' => '2026-11-01T10:00:00.000000Z', 'updated_at' => null];
$log = [
    'id' => 70, 'provider' => 'ses', 'operation' => 'SendEmail', 'recipient' => 'ana@example.com', 'message_id' => 'msg-1',
    'status' => 'success', 'error' => null, 'request_payload' => ['To' => 'ana@example.com'], 'response_payload' => null,
    'sns_payload' => null, 'logged_at' => '2026-11-01T10:00:00.000000Z', 'created_at' => '2026-11-01T10:00:00.000000Z',
];
$project = ['id' => 1, 'uuid' => 'a1b2c3d4-0000-4000-8000-000000000010', 'name' => 'Launch', 'newsletters_count' => 2, 'sequences_count' => 1, 'automations_count' => 0, 'visual_automations_count' => 0, 'created_at' => '2026-11-01T10:00:00.000000Z'];

it('lists, reads, creates, updates and deletes blocks', function () use ($block) {
    $history = [];
    $client = byomailer([json(['data' => [$block]]), json(['data' => $block]), json(['data' => $block], 201), json(['data' => $block]), noContent()], $history);

    expect($client->blocks()->list()[0]->name)->toBe('footer')
        ->and($client->blocks()->find(1)->htmlContent)->toBe('<p>Bye</p>')
        ->and($client->blocks()->create('footer', '<p>Bye</p>')->id)->toBe(1)
        ->and($client->blocks()->update(1, 'footer', '<p>Bye</p>')->uuid)->toBe($block['uuid']);

    $client->blocks()->delete(1);

    expect(array_map(fn (array $entry): string => sent($entry['request']), $history))->toBe([
        'GET /api/v1/blocks',
        'GET /api/v1/blocks/1',
        'POST /api/v1/blocks',
        'PATCH /api/v1/blocks/1',
        'DELETE /api/v1/blocks/1',
    ])->and(body($history[2]['request']))->toBe(['name' => 'footer', 'html_content' => '<p>Bye</p>']);
});

it('lists, reads and reads the default email template', function () use ($template) {
    $history = [];
    $client = byomailer([json(page([$template])), json(['data' => $template]), json(['data' => $template])], $history);

    expect($client->emailTemplates()->list(archived: true, isDefault: false, perPage: 5)->first()?->isDefault)->toBeTrue()
        ->and($client->emailTemplates()->find(6)->name)->toBe('Brand')
        ->and($client->emailTemplates()->default()->id)->toBe(6);

    expect(array_map(fn (array $entry): string => sent($entry['request']), $history))->toBe([
        'GET /api/v1/email-templates?archived=1&is_default=0&per_page=5',
        'GET /api/v1/email-templates/6',
        'GET /api/v1/email-templates/default',
    ]);
});

it('lists and reads email identities', function () {
    $history = [];
    $client = byomailer([json(page([identityPayload()])), json(['data' => identityPayload()])], $history);

    expect($client->emailIdentities()->list(status: 'verified')->first()?->email)->toBe('news@example.com')
        ->and($client->emailIdentities()->find(2)->domain)->toBe('example.com');

    expect(sent($history[0]['request']))->toBe('GET /api/v1/email-identities?identity_status=verified')
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/email-identities/2');
});

it('lists and reads email logs', function () use ($log) {
    $history = [];
    $client = byomailer([json(page([$log])), json(['data' => $log])], $history);

    expect($client->emailLogs()->list(status: 'success', operation: 'SendEmail', recipient: 'ana')->first()?->messageId)->toBe('msg-1')
        ->and($client->emailLogs()->find(70)->requestPayload)->toBe(['To' => 'ana@example.com']);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/email-logs?status=success&operation=SendEmail&recipient=ana')
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/email-logs/70');
});

it('lists, reads, creates, renames and deletes projects', function () use ($project) {
    $history = [];
    $created = ['id' => 1, 'uuid' => $project['uuid'], 'name' => 'Launch', 'created_at' => '2026-11-01T10:00:00.000000Z'];
    $client = byomailer([json(page([$project])), json(['data' => $project]), json(['data' => $created], 201), json(['data' => $created]), noContent()], $history);

    expect($client->projects()->list()->first()?->newslettersCount)->toBe(2)
        ->and($client->projects()->find(1)->sequencesCount)->toBe(1)
        ->and($client->projects()->create('Launch')->newslettersCount)->toBeNull()
        ->and($client->projects()->rename(1, 'Launch')->name)->toBe('Launch');

    $client->projects()->delete(1);

    expect(array_map(fn (array $entry): string => sent($entry['request']), $history))->toBe([
        'GET /api/v1/projects',
        'GET /api/v1/projects/1',
        'POST /api/v1/projects',
        'PATCH /api/v1/projects/1',
        'DELETE /api/v1/projects/1',
    ]);
});
