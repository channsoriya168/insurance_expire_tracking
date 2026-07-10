<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ExpiryDateRange
{
    public function __construct(
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    /**
     * Parse a user-supplied filter string into a date range.
     *
     * Accepts: "" or "all" (no filter), "YYYY-MM" (a whole month),
     * or "YYYY-MM-DD..YYYY-MM-DD" (an explicit range).
     */
    public static function parse(string $input): self
    {
        $input = trim($input);

        if ($input === '' || strcasecmp($input, 'all') === 0) {
            return new self;
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $input, $matches) === 1) {
            $start = CarbonImmutable::createFromDate((int) $matches[1], (int) $matches[2], 1)->startOfMonth();

            return new self($start, $start->endOfMonth());
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/', $input, $matches) === 1) {
            return new self(
                CarbonImmutable::parse($matches[1])->startOfDay(),
                CarbonImmutable::parse($matches[2])->endOfDay(),
            );
        }

        throw new InvalidArgumentException(
            "Unrecognized filter \"{$input}\". Use \"all\", \"YYYY-MM\", or \"YYYY-MM-DD..YYYY-MM-DD\"."
        );
    }

    public function isUnfiltered(): bool
    {
        return $this->from === null && $this->to === null;
    }
}
