<?php

declare(strict_types=1);

namespace Byomailer\Data;

/**
 * The `{{ variable }}` placeholders of a newsletter or a sequence, and the
 * default value of each one. A default fills a placeholder when the contact
 * has no value for it.
 */
final readonly class ContentVariables
{
    /**
     * @param  list<string>  $detected  The variables that the content uses.
     * @param  array<string, string>  $defaults  Each saved default, plus an empty default for each detected variable with no saved default.
     */
    public function __construct(
        public array $detected,
        public array $defaults,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);

        return new self(
            detected: $payload->strings('detected_variables'),
            defaults: $payload->stringMap('content_variables'),
        );
    }

    public function default(string $variable): ?string
    {
        return $this->defaults[$variable] ?? null;
    }

    /**
     * The detected variables that have no default, or an empty default.
     *
     * @return list<string>
     */
    public function missing(): array
    {
        return array_values(array_filter(
            $this->detected,
            fn (string $variable): bool => ($this->defaults[$variable] ?? '') === '',
        ));
    }
}
