<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\Contact;
use Byomailer\Data\CursorPage;
use Generator;
use InvalidArgumentException;

/**
 * The contacts of your client.
 */
final readonly class Contacts extends Resource
{
    /**
     * The body fields that are not custom attributes.
     */
    private const RESERVED = ['email', 'name', 'tags', 'remove_tags'];

    /**
     * One page of contacts, newest first. The list leaves out unsubscribed
     * contacts, unless `$includeUnsubscribed` is true.
     *
     * @param  list<int>  $lists  Only contacts on one of these lists.
     * @param  list<int>  $tags  Only contacts with one of these tags.
     * @param  list<int>  $topics  Only contacts subscribed to one of these topics.
     * @param  string|null  $search  Matches part of the name or the email.
     * @return CursorPage<Contact>
     */
    public function list(
        array $lists = [],
        array $tags = [],
        array $topics = [],
        ?string $search = null,
        bool $includeUnsubscribed = false,
        ?int $perPage = null,
        ?string $cursor = null,
    ): CursorPage {
        $body = $this->transport->get('contacts', self::filled([
            'lists' => $lists ?: null,
            'tags' => $tags ?: null,
            'topics' => $topics ?: null,
            'search' => $search,
            'include_unsubscribed' => $includeUnsubscribed ? 1 : null,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, Contact::fromArray(...));
    }

    /**
     * Every contact that matches the filters. The SDK reads the next page when
     * you reach the end of the current page.
     *
     * @param  list<int>  $lists
     * @param  list<int>  $tags
     * @param  list<int>  $topics
     * @return Generator<int, Contact>
     */
    public function all(
        array $lists = [],
        array $tags = [],
        array $topics = [],
        ?string $search = null,
        bool $includeUnsubscribed = false,
        ?int $perPage = null,
    ): Generator {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list(
            $lists, $tags, $topics, $search, $includeUnsubscribed, $perPage, $cursor,
        ));
    }

    public function find(int $contactId): Contact
    {
        return Contact::fromArray($this->transport->get("contacts/{$contactId}")->data());
    }

    /**
     * Add a contact to a list. If a contact with this email exists, the
     * instance updates it and adds it to the list. Read `wasRecentlyCreated`
     * on the result to know which one occurred.
     *
     * If an admin or the API unsubscribed the contact, the instance subscribes
     * the contact again. An unsubscribe through an email link stays. If the
     * list has opt-in, only a contact that is new on the list gets the opt-in email.
     *
     * @param  list<string>  $tags  Tag names to add. The instance creates a tag that does not exist.
     * @param  array<string, mixed>  $attributes  Custom attributes, for example `['company' => 'Acme']`.
     */
    public function addToList(
        int $listId,
        string $email,
        ?string $name = null,
        array $tags = [],
        array $attributes = [],
    ): Contact {
        $response = $this->transport->post("lists/{$listId}/contacts", [
            ...self::attributes($attributes),
            ...self::filled([
                'email' => $email,
                'name' => $name,
                'tags' => $tags ?: null,
            ]),
        ]);

        return Contact::fromArray($response->data(), wasRecentlyCreated: $response->status === 201);
    }

    /**
     * Change a contact. A field that you do not give keeps its value. Give an
     * empty string as the name to remove the name.
     *
     * An update never changes the subscription. To subscribe a contact again,
     * use `subscribe()` or `addToList()`.
     *
     * @param  list<string>  $tags  Tag names to add.
     * @param  list<string>  $removeTags  Tag names to remove.
     * @param  array<string, mixed>  $attributes  Custom attributes to set. The other attributes keep their values.
     */
    public function update(
        int $contactId,
        ?string $email = null,
        ?string $name = null,
        array $tags = [],
        array $removeTags = [],
        array $attributes = [],
    ): Contact {
        $response = $this->transport->patch("contacts/{$contactId}", [
            ...self::attributes($attributes),
            ...self::filled([
                'email' => $email,
                'name' => $name,
                'tags' => $tags ?: null,
                'remove_tags' => $removeTags ?: null,
            ]),
        ], idempotent: true);

        return Contact::fromArray($response->data());
    }

    /**
     * Unsubscribe a contact from all email. The contact stays on its lists.
     */
    public function unsubscribe(int $contactId): Contact
    {
        return Contact::fromArray(
            $this->transport->post("contacts/{$contactId}/unsubscribe", idempotent: true)->data()
        );
    }

    /**
     * Subscribe a contact again after an admin or the API unsubscribed it.
     * A subscribed contact does not change.
     *
     * In two cases, the instance refuses the request with a
     * `ValidationException`. If the contact unsubscribed through an email
     * link, the error is on the field `contact`. If the address is
     * blacklisted, the error is on the field `email`.
     */
    public function subscribe(int $contactId): Contact
    {
        return Contact::fromArray(
            $this->transport->post("contacts/{$contactId}/subscribe", idempotent: true)->data()
        );
    }

    /**
     * Remove a contact from one list. The instance keeps the contact.
     */
    public function removeFromList(int $listId, int $contactId): Contact
    {
        return Contact::fromArray($this->transport->delete("lists/{$listId}/contacts/{$contactId}")->data());
    }

    /**
     * The instance reads each body field that is not `email`, `name`, `tags`
     * or `remove_tags` as a custom attribute.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function attributes(array $attributes): array
    {
        $reserved = array_intersect(array_map('strval', array_keys($attributes)), self::RESERVED);

        if ($reserved !== []) {
            throw new InvalidArgumentException(
                'These names are fields of the contact, not custom attributes: '.implode(', ', $reserved).'. Use the named arguments.'
            );
        }

        return $attributes;
    }
}
