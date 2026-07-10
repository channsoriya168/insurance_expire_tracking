<?php

namespace App\Telegram\Conversations;

final readonly class FieldParseResult
{
    private function __construct(
        public bool $ok,
        public mixed $value = null,
        public ?string $error = null,
    ) {}

    public static function ok(mixed $value): self
    {
        return new self(true, $value);
    }

    public static function fail(string $error): self
    {
        return new self(false, error: $error);
    }
}
