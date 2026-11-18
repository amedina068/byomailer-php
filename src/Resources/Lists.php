<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\MailList;
use Generator;

/**
 * The mailing lists of your client.
 */
final readonly class Lists extends Resource
{
    /**
     * One page of lists, newest first.
     *
     * @return CursorPage<MailList>
     */
    public function list(?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get('lists', self::filled(['per_page' => $perPage, 'cursor' => $cursor]))->json();

        return CursorPage::fromBody($body, MailList::fromArray(...));
    }

    /**
     * @return Generator<int, MailList>
     */
    public function all(?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($perPage, $cursor));
    }

    public function create(string $name): MailList
    {
        return MailList::fromArray($this->transport->post('lists', ['name' => $name])->data());
    }

    public function rename(int $listId, string $name): MailList
    {
        return MailList::fromArray($this->transport->patch("lists/{$listId}", ['name' => $name], idempotent: true)->data());
    }

    public function delete(int $listId): void
    {
        $this->transport->delete("lists/{$listId}");
    }
}
