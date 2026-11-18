<?php

declare(strict_types=1);

namespace Byomailer\Data;

use ArrayIterator;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * One page of a list that uses cursors.
 *
 * Give `$nextCursor` to the same list method to read the next page, or use
 * the `all()` method of the resource to read every page.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class CursorPage implements Countable, IteratorAggregate
{
    /**
     * @param  list<TItem>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor = null,
        public ?string $previousCursor = null,
    ) {}

    /**
     * @template TValue
     *
     * @param  array<array-key, mixed>  $body
     * @param  callable(array<array-key, mixed>): TValue  $map
     * @return self<TValue>
     */
    public static function fromBody(array $body, callable $map): self
    {
        $meta = Payload::of(Payload::of($body)->array('meta'));

        return new self(
            array_map($map, Payload::of($body)->objects('data')),
            $meta->nullableString('next_cursor'),
            $meta->nullableString('prev_cursor'),
        );
    }

    /**
     * Read each page, from the first page to the last, and yield each item.
     * The SDK reads a page only when the caller reaches it.
     *
     * @template TValue
     *
     * @param  Closure(?string): self<TValue>  $page  Reads the page at a cursor. A null cursor is the first page.
     * @return Generator<int, TValue>
     */
    public static function walk(Closure $page): Generator
    {
        $cursor = null;

        do {
            $current = $page($cursor);

            foreach ($current->items as $item) {
                yield $item;
            }

            $cursor = $current->nextCursor;
        } while ($cursor !== null);
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return TItem|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, TItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
