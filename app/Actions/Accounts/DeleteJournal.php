<?php

namespace App\Actions\Accounts;

use App\Models\Accounts\Journal;
use App\Models\Contracts\Journalable;
use Illuminate\Support\Collection;

class DeleteJournal
{
    public function handle(Journalable $model): void
    {
        /** @var Collection<int, Journal> $journals */
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $journals = $model->journal()->get();
        foreach ($journals as $journal) {
            $journal->transactions()->delete();
        }
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model->journal()->delete();
    }
}
