<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Http\Transport;

/**
 * The parent of each API resource.
 *
 * @internal
 */
abstract readonly class Resource
{
    public function __construct(protected Transport $transport) {}

    /**
     * Remove the null values, so that the instance keeps the current value of
     * each field that the caller did not give.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function filled(array $data): array
    {
        return array_filter($data, static fn (mixed $value): bool => $value !== null);
    }
}
