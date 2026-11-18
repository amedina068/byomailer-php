<?php

declare(strict_types=1);

namespace Byomailer\Data;

use Byomailer\Exceptions\UnexpectedResponseException;
use DateTimeImmutable;
use Throwable;

/**
 * Reads typed fields from one decoded JSON object.
 *
 * The value objects use this class so that a missing or a wrong field gives
 * one clear exception, not a PHP type error.
 *
 * @internal
 */
final readonly class Payload
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(private array $data) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function of(array $data): self
    {
        return new self($data);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function string(string $key): string
    {
        $value = $this->data[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw UnexpectedResponseException::field($key, 'string');
    }

    public function nullableString(string $key): ?string
    {
        return ($this->data[$key] ?? null) === null ? null : $this->string($key);
    }

    public function int(string $key): int
    {
        $value = $this->data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw UnexpectedResponseException::field($key, 'integer');
    }

    public function nullableInt(string $key): ?int
    {
        return ($this->data[$key] ?? null) === null ? null : $this->int($key);
    }

    public function bool(string $key): bool
    {
        $value = $this->data[$key] ?? false;

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function date(string $key): ?DateTimeImmutable
    {
        $value = $this->nullableString($key);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            throw UnexpectedResponseException::field($key, 'date');
        }
    }

    /**
     * The array under one key. A missing key or null gives an empty array.
     *
     * @return array<array-key, mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? [];

        if (! is_array($value)) {
            throw UnexpectedResponseException::field($key, 'array');
        }

        return $value;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function nullableArray(string $key): ?array
    {
        return ($this->data[$key] ?? null) === null ? null : $this->array($key);
    }

    /**
     * A JSON object of names to text, for example the content variables.
     *
     * @return array<string, string>
     */
    public function stringMap(string $key): array
    {
        $map = [];

        foreach ($this->array($key) as $name => $value) {
            $map[(string) $name] = is_scalar($value) ? (string) $value : '';
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        return array_values(array_map(
            static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
            $this->array($key),
        ));
    }

    /**
     * @return list<int>|null
     */
    public function nullableInts(string $key): ?array
    {
        if (($this->data[$key] ?? null) === null) {
            return null;
        }

        return array_values(array_map(
            static fn (mixed $value): int => is_numeric($value) ? (int) $value : 0,
            $this->array($key),
        ));
    }

    /**
     * The list of JSON objects under one key.
     *
     * @return list<array<array-key, mixed>>
     */
    public function objects(string $key): array
    {
        return self::listOfObjects($this->array($key), $key);
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    public static function listOfObjects(mixed $value, string $key = 'data'): array
    {
        if (! is_array($value)) {
            throw UnexpectedResponseException::field($key, 'array');
        }

        $objects = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                throw UnexpectedResponseException::field($key, 'list of objects');
            }

            $objects[] = $item;
        }

        return $objects;
    }
}
