<?php

declare(strict_types=1);

it('lists the lists', function () {
    $history = [];

    $page = byomailer([json(page([listPayload(['contacts_count' => 12])]))], $history)->lists()->list(perPage: 10);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/lists?per_page=10')
        ->and($page->first()?->name)->toBe('Customers')
        ->and($page->first()?->contactsCount)->toBe(12)
        ->and($page->hasMore())->toBeFalse();
});

it('creates, renames and deletes a list', function () {
    $history = [];
    $client = byomailer([json(['data' => listPayload()], 201), json(['data' => listPayload(['name' => 'Buyers'])]), noContent()], $history);

    expect($client->lists()->create('Customers')->id)->toBe(3)
        ->and($client->lists()->rename(3, 'Buyers')->name)->toBe('Buyers');

    $client->lists()->delete(3);

    expect(array_map(fn (array $entry): string => sent($entry['request']), $history))->toBe([
        'POST /api/v1/lists',
        'PATCH /api/v1/lists/3',
        'DELETE /api/v1/lists/3',
    ])->and(body($history[1]['request']))->toBe(['name' => 'Buyers']);
});

it('lists the topics of a list with no pages', function () {
    $history = [];

    $topics = byomailer([json(['data' => [topicPayload(), topicPayload(['id' => 9, 'name' => 'Events'])]])], $history)->topics()->list(3);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/lists/3/topics')
        ->and($topics)->toHaveCount(2)
        ->and($topics[1]->name)->toBe('Events')
        ->and($topics[0]->listId)->toBe(3);
});

it('creates a topic', function () {
    $history = [];

    byomailer([json(['data' => topicPayload()], 201)], $history)->topics()->create(3, 'Product news', 'Releases and fixes');

    expect(sent($history[0]['request']))->toBe('POST /api/v1/lists/3/topics')
        ->and(body($history[0]['request']))->toBe(['name' => 'Product news', 'description' => 'Releases and fixes']);
});

it('replaces the name and the description of a topic', function () {
    $history = [];

    byomailer([json(['data' => topicPayload()])], $history)->topics()->update(3, 8, 'Product news');

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/lists/3/topics/8')
        ->and(body($history[0]['request']))->toBe(['name' => 'Product news', 'description' => null]);
});

it('deletes a topic', function () {
    $history = [];

    byomailer([noContent()], $history)->topics()->delete(3, 8);

    expect(sent($history[0]['request']))->toBe('DELETE /api/v1/lists/3/topics/8');
});

it('lists the tags with no pages', function () {
    $tags = byomailer([json(['data' => [tagPayload(['contacts_count' => 4])]])])->tags()->list();

    expect($tags[0]->name)->toBe('vip')
        ->and($tags[0]->contactsCount)->toBe(4);
});

it('renames and deletes a tag', function () {
    $history = [];
    $client = byomailer([json(['data' => tagPayload(['name' => 'gold'])]), noContent()], $history);

    expect($client->tags()->rename(5, 'gold')->name)->toBe('gold');

    $client->tags()->delete(5);

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/tags/5')
        ->and(sent($history[1]['request']))->toBe('DELETE /api/v1/tags/5');
});
