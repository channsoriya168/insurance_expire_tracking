<?php

namespace App\Telegram\Conversations;

use Closure;

final readonly class FieldStep
{
    /**
     * @param  Closure(string, ?int): FieldParseResult  $parse
     */
    public function __construct(
        public string $key,
        public string $prompt,
        public bool $skippable,
        public Closure $parse,
    ) {}
}
