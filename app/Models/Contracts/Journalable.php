<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Journalable
{
    public function journal(): MorphOne;

    public function journalDetail(): string;

    #[Scope]
    public function confirmedBetween(Builder $query, $startDate, $endDate): Builder;

    #[Scope]
    public function confirmedBefore(Builder $query, $date): Builder;

    #[Scope]
    public function confirmedAfter(Builder $query, $date): Builder;

    #[Scope]
    public function confirmedOn(Builder $query, $date): Builder;

    #[Scope]
    public function confirmed(Builder $query): Builder;
}
