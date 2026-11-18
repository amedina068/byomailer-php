<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A person who receives email.
 */
final readonly class Contact
{
    /**
     * @param  array<string, mixed>  $attributes  The custom attributes, for example `['company' => 'Acme']`.
     * @param  list<Tag>  $tags
     * @param  list<MailList>  $lists
     * @param  list<Topic>  $topics  The topics that the contact is subscribed to.
     * @param  list<array{id: int, name: string, status: string}>  $sequences
     * @param  bool  $wasRecentlyCreated  True when `addToList()` created the contact. False when the contact existed.
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public ?string $name = null,
        public array $attributes = [],
        public ?DateTimeImmutable $unsubscribedAt = null,
        public bool $unsubscribedByContact = false,
        public array $tags = [],
        public array $lists = [],
        public array $topics = [],
        public array $sequences = [],
        public ?DateTimeImmutable $createdAt = null,
        public bool $wasRecentlyCreated = false,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data, bool $wasRecentlyCreated = false): self
    {
        $payload = Payload::of($data);

        return new self(
            id: $payload->int('id'),
            uuid: $payload->string('uuid'),
            email: $payload->string('email'),
            name: $payload->nullableString('name'),
            attributes: self::attributes($payload->array('extra_attributes')),
            unsubscribedAt: $payload->date('unsubscribed_at'),
            unsubscribedByContact: $payload->bool('unsubscribed_by_contact'),
            tags: array_map(Tag::fromArray(...), $payload->objects('tags')),
            lists: array_map(MailList::fromArray(...), $payload->objects('lists')),
            topics: array_map(Topic::fromArray(...), $payload->objects('topics')),
            sequences: array_map(self::sequence(...), $payload->objects('sequences')),
            createdAt: $payload->date('created_at'),
            wasRecentlyCreated: $wasRecentlyCreated,
        );
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribedAt === null;
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function tagNames(): array
    {
        return array_map(static fn (Tag $tag): string => $tag->name, $this->tags);
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function attributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $name => $value) {
            $result[(string) $name] = $value;
        }

        return $result;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array{id: int, name: string, status: string}
     */
    private static function sequence(array $data): array
    {
        $payload = Payload::of($data);

        return [
            'id' => $payload->int('id'),
            'name' => $payload->string('name'),
            'status' => $payload->string('status'),
        ];
    }
}
