<?php

declare(strict_types=1);

use Byomailer\Data\CursorPage;

it('walks every page with the cursor and the page size', function () {
    $history = [];
    $client = byomailer([
        json(page([listPayload(['id' => 3]), listPayload(['id' => 2])], next: 'c2')),
        json(page([listPayload(['id' => 1])], previous: 'c1')),
    ], $history);

    $ids = array_map(fn ($list) => $list->id, iterator_to_array($client->lists()->all(perPage: 2)));

    expect($ids)->toBe([3, 2, 1])
        ->and(sent($history[0]['request']))->toBe('GET /api/v1/lists?per_page=2')
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/lists?per_page=2&cursor=c2');
});

it('reads a page only when the caller reaches it', function () {
    $history = [];
    $client = byomailer([json(page([contactPayload()], next: 'c2')), json(page([contactPayload()]))], $history);

    $contacts = $client->contacts()->all(tags: [5]);
    $contacts->current();

    expect($history)->toHaveCount(1)
        ->and(sent($history[0]['request']))->toBe('GET /api/v1/contacts?tags[0]=5');
});

it('keeps the filters on each page', function () {
    $history = [];
    $client = byomailer([json(page([deliveryPayload()], next: 'c2')), json(page([deliveryPayload()]))], $history);

    expect(iterator_to_array($client->webhookEndpoints()->allDeliveries(4, perPage: 1)))->toHaveCount(2)
        ->and(sent($history[1]['request']))->toBe('GET /api/v1/webhook-endpoints/4/deliveries?per_page=1&cursor=c2');
});

it('walks each paginated resource', function (Closure $all, array $item) {
    $client = byomailer([json(page([$item], next: 'c2')), json(page([$item]))]);

    expect(iterator_to_array($all($client)))->toHaveCount(2);
})->with([
    'contacts' => [fn ($client) => $client->contacts()->all(), contactPayload()],
    'newsletters' => [fn ($client) => $client->newsletters()->all(), newsletterPayload()],
    'sequences' => [fn ($client) => $client->sequences()->all(), sequenceSummaryPayload()],
    'email templates' => [fn ($client) => $client->emailTemplates()->all(), ['id' => 6, 'name' => 'Brand', 'html_content' => '', 'is_default' => false]],
    'email identities' => [fn ($client) => $client->emailIdentities()->all(), identityPayload()],
    'email logs' => [fn ($client) => $client->emailLogs()->all(), ['id' => 1, 'provider' => 'ses', 'operation' => 'SendEmail', 'status' => 'success']],
    'projects' => [fn ($client) => $client->projects()->all(), ['id' => 1, 'uuid' => 'u', 'name' => 'Launch']],
    'webhook endpoints' => [fn ($client) => $client->webhookEndpoints()->all(), endpointPayload()],
]);

it('reads the page metadata', function () {
    $page = CursorPage::fromBody(page([['id' => 1]], next: 'n', previous: 'p'), fn (array $item) => $item['id']);

    expect($page->items)->toBe([1])
        ->and($page->nextCursor)->toBe('n')
        ->and($page->previousCursor)->toBe('p')
        ->and($page->count())->toBe(1)
        ->and($page->isEmpty())->toBeFalse()
        ->and(iterator_to_array($page))->toBe([1]);
});

it('stops after an empty first page', function () {
    $history = [];

    expect(iterator_to_array(byomailer([json(page([]))], $history)->projects()->all()))->toBe([])
        ->and($history)->toHaveCount(1);
});
