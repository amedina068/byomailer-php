<?php

declare(strict_types=1);

use Byomailer\Data\Contact;
use Byomailer\Data\MailList;
use Byomailer\Data\Tag;
use Byomailer\Data\Topic;

it('lists contacts with the filters', function () {
    $history = [];

    $page = byomailer([json(page([contactPayload()], next: 'eyJpZCI6NDJ9'))], $history)->contacts()->list(
        lists: [3, 4],
        tags: [5],
        topics: [8],
        search: 'ana',
        includeUnsubscribed: true,
        perPage: 50,
        cursor: 'abc',
    );

    expect(sent($history[0]['request']))->toBe('GET /api/v1/contacts?lists[0]=3&lists[1]=4&tags[0]=5&topics[0]=8&search=ana&include_unsubscribed=1&per_page=50&cursor=abc')
        ->and($page->items)->toHaveCount(1)
        ->and($page->nextCursor)->toBe('eyJpZCI6NDJ9')
        ->and($page->hasMore())->toBeTrue();
});

it('sends no query when no filter is given', function () {
    $history = [];

    byomailer([json(page([]))], $history)->contacts()->list();

    expect(sent($history[0]['request']))->toBe('GET /api/v1/contacts');
});

it('reads a contact with its relations', function () {
    $history = [];

    $contact = byomailer([json(['data' => contactPayload()])], $history)->contacts()->find(42);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/contacts/42')
        ->and($contact->id)->toBe(42)
        ->and($contact->email)->toBe('ana@example.com')
        ->and($contact->attributes)->toBe(['company' => 'Acme'])
        ->and($contact->attribute('company'))->toBe('Acme')
        ->and($contact->attribute('plan', 'free'))->toBe('free')
        ->and($contact->isSubscribed())->toBeTrue()
        ->and($contact->tags[0])->toBeInstanceOf(Tag::class)
        ->and($contact->tagNames())->toBe(['vip'])
        ->and($contact->lists[0])->toBeInstanceOf(MailList::class)
        ->and($contact->topics[0])->toBeInstanceOf(Topic::class)
        ->and($contact->sequences)->toBe([['id' => 3, 'name' => 'Welcome', 'status' => 'live']])
        ->and($contact->createdAt?->format('Y-m-d'))->toBe('2026-11-01')
        ->and($contact->wasRecentlyCreated)->toBeFalse();
});

it('reads an empty attribute list as an empty map', function () {
    $contact = Contact::fromArray(contactPayload(['extra_attributes' => []]));

    expect($contact->attributes)->toBe([]);
});

it('adds a new contact to a list with the attributes at the top level', function () {
    $history = [];

    $contact = byomailer([json(['data' => contactPayload()], 201)], $history)->contacts()->addToList(
        3,
        'ana@example.com',
        name: 'Ana',
        tags: ['vip'],
        attributes: ['company' => 'Acme', 'plan' => 'pro'],
    );

    expect(sent($history[0]['request']))->toBe('POST /api/v1/lists/3/contacts')
        ->and(body($history[0]['request']))->toBe([
            'company' => 'Acme',
            'plan' => 'pro',
            'email' => 'ana@example.com',
            'name' => 'Ana',
            'tags' => ['vip'],
        ])
        ->and($contact->wasRecentlyCreated)->toBeTrue();
});

it('tells when the contact existed before', function () {
    $contact = byomailer([json(['data' => contactPayload()], 200)])->contacts()->addToList(3, 'ana@example.com');

    expect($contact->wasRecentlyCreated)->toBeFalse();
});

it('sends only the email when no other field is given', function () {
    $history = [];

    byomailer([json(['data' => contactPayload()], 201)], $history)->contacts()->addToList(3, 'ana@example.com');

    expect(body($history[0]['request']))->toBe(['email' => 'ana@example.com']);
});

it('refuses an attribute with the name of a contact field', function (string $name) {
    byomailer()->contacts()->addToList(3, 'ana@example.com', attributes: [$name => 'x']);
})->with(['email', 'name', 'tags', 'remove_tags'])->throws(InvalidArgumentException::class, 'not custom attributes');

it('updates a contact with PATCH', function () {
    $history = [];

    byomailer([json(['data' => contactPayload()])], $history)->contacts()->update(
        42,
        name: 'Ana Pérez',
        tags: ['customer'],
        removeTags: ['lead'],
        attributes: ['plan' => 'pro'],
    );

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/contacts/42')
        ->and(body($history[0]['request']))->toBe([
            'plan' => 'pro',
            'name' => 'Ana Pérez',
            'tags' => ['customer'],
            'remove_tags' => ['lead'],
        ]);
});

it('unsubscribes a contact', function () {
    $history = [];

    $contact = byomailer([json(['data' => contactPayload(['unsubscribed_at' => '2026-11-02T10:00:00.000000Z'])])], $history)
        ->contacts()->unsubscribe(42);

    expect(sent($history[0]['request']))->toBe('POST /api/v1/contacts/42/unsubscribe')
        ->and($contact->isSubscribed())->toBeFalse();
});

it('removes a contact from a list and returns the contact', function () {
    $history = [];

    $contact = byomailer([json(['data' => contactPayload(['lists' => []])])], $history)->contacts()->removeFromList(3, 42);

    expect(sent($history[0]['request']))->toBe('DELETE /api/v1/lists/3/contacts/42')
        ->and($contact->lists)->toBe([]);
});
