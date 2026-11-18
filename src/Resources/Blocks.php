<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\Block;
use Byomailer\Data\Payload;

/**
 * The reusable HTML blocks of your client.
 */
final readonly class Blocks extends Resource
{
    /**
     * Every block, newest first. This endpoint has no pages.
     *
     * @return list<Block>
     */
    public function list(): array
    {
        return array_map(Block::fromArray(...), Payload::of($this->transport->get('blocks')->json())->objects('data'));
    }

    public function find(int $blockId): Block
    {
        return Block::fromArray($this->transport->get("blocks/{$blockId}")->data());
    }

    /**
     * @param  string  $name  Lowercase letters, numbers and underscores only. The name is unique.
     */
    public function create(string $name, string $htmlContent): Block
    {
        return Block::fromArray($this->transport->post('blocks', [
            'name' => $name,
            'html_content' => $htmlContent,
        ])->data());
    }

    /**
     * Replace the name and the HTML of a block.
     */
    public function update(int $blockId, string $name, string $htmlContent): Block
    {
        return Block::fromArray($this->transport->patch("blocks/{$blockId}", [
            'name' => $name,
            'html_content' => $htmlContent,
        ], idempotent: true)->data());
    }

    public function delete(int $blockId): void
    {
        $this->transport->delete("blocks/{$blockId}");
    }
}
