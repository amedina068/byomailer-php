<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\Payload;
use Byomailer\Data\Tag;

/**
 * The tags of the token user.
 */
final readonly class Tags extends Resource
{
    /**
     * Every tag, newest first. This endpoint has no pages.
     *
     * @return list<Tag>
     */
    public function list(): array
    {
        return array_map(Tag::fromArray(...), Payload::of($this->transport->get('tags')->json())->objects('data'));
    }

    public function create(string $name): Tag
    {
        return Tag::fromArray($this->transport->post('tags', ['name' => $name])->data());
    }

    public function rename(int $tagId, string $name): Tag
    {
        return Tag::fromArray($this->transport->patch("tags/{$tagId}", ['name' => $name], idempotent: true)->data());
    }

    /**
     * Delete a tag. The instance removes the tag from each contact.
     */
    public function delete(int $tagId): void
    {
        $this->transport->delete("tags/{$tagId}");
    }
}
