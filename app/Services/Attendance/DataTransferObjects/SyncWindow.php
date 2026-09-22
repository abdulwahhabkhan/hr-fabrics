<?php

namespace App\Services\Attendance\DataTransferObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class SyncWindow
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {
        if ($from->greaterThanOrEqualTo($to)) {
            throw new InvalidArgumentException('Sync window "from" must precede "to".');
        }
    }

    /**
     * Clamp the window so a single run can never fetch an unbounded range.
     */
    public function clampTo(int $maxDays): self
    {
        $limit = $this->from->addDays($maxDays);

        return $this->to->greaterThan($limit)
            ? new self($this->from, $limit)
            : $this;
    }

    public function describe(): string
    {
        return $this->from->toDateTimeString().' → '.$this->to->toDateTimeString();
    }
}
