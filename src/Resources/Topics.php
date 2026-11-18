<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\Payload;
use Byomailer\Data\Topic;

/**
 * The topics of a list. The instance shows only the lists of the token user.
 */
final readonly class Topics extends Resource
{
    /**
     * Every topic of a list, newest first. This endpoint has no pages.
     *
     * @return list<Topic>
     */
    public function list(int $listId): array
    {
        $body = $this->transport->get("lists/{$listId}/topics")->json();

        return array_map(Topic::fromArray(...), Payload::of($body)->objects('data'));
    }

    /**
     * Create a topic. The instance subscribes the contacts of the list to it.
     */
    public function create(int $listId, string $name, ?string $description = null): Topic
    {
        $response = $this->transport->post("lists/{$listId}/topics", self::filled([
            'name' => $name,
            'description' => $description,
        ]));

        return Topic::fromArray($response->data());
    }

    /**
     * Replace the name and the description of a topic. A null description
     * removes the description.
     */
    public function update(int $listId, int $topicId, string $name, ?string $description = null): Topic
    {
        $response = $this->transport->patch("lists/{$listId}/topics/{$topicId}", [
            'name' => $name,
            'description' => $description,
        ], idempotent: true);

        return Topic::fromArray($response->data());
    }

    public function delete(int $listId, int $topicId): void
    {
        $this->transport->delete("lists/{$listId}/topics/{$topicId}");
    }
}
