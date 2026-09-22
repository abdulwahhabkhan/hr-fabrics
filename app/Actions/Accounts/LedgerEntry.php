<?php

namespace App\Actions\Accounts;

use App\Actions\LogAction\RecordAction;
use App\Enums\JournalHead;
use App\Models\Accounts\Journal;
use App\Models\File;
use App\Models\Model;
use App\Models\User;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Traits\Conditionable;

final class LedgerEntry
{
    use Conditionable;

    public ?Model $morph = null;

    private User $user;

    private JournalHead $head;

    private CarbonInterface $transactionDate;

    private Journal $journal;

    private string $logAction = 'Ledger Entry';

    private ?string $detail = null;

    private ?array $file = null;

    public function setFile(array $file): self
    {
        unset($file['file_thumbnail_url'], $file['file_download_url']);
        $this->file = $file;

        return $this;
    }

    public function setDetail(string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    public function setLogAction(string $logAction): self
    {
        $this->logAction = $logAction;

        return $this;
    }

    public function init(?Model $model = null): self
    {
        $this->morph = $model;
        $this->createJournal();

        return $this;
    }

    public function debit(int $accountId, float $amount): self
    {
        $this->journal->transactions()->create([
            'account_id' => $accountId,
            'dr' => $amount,
            'cr' => 0,
        ]);

        return $this;
    }

    public function credit(int $accountId, float $amount): self
    {
        $this->journal->transactions()->create([
            'account_id' => $accountId,
            'dr' => 0,
            'cr' => $amount,
        ]);

        return $this;
    }

    public function setTransactionDate(CarbonInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setHead(JournalHead $head): self
    {
        $this->head = $head;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function logAction(): void
    {
        resolve(RecordAction::class)->handle(
            model: $this->morph ?? $this->journal,
            user: $this->user,
            action: $this->logAction,
            log: [
                'date' => $this->transactionDate,
                'detail' => $this->morph?->journalDetail() ?? $this->detail,
                'head' => $this->head,
            ]
        );
    }

    private function createJournal(): void
    {
        $journal = new Journal();
        $id = (int) Journal::query()->max('id');
        $reference_no = str($id + 1)->padLeft(5, '0')->prepend('JV-');
        $journal->reference_no = $reference_no;
        $journal->user_id = $this->user->id;
        $journal->head = $this->head;
        $journal->posted_at = $this->transactionDate;
        if ($this->morph) {
            $journal->detail = $this->morph->journalDetail();
            $journal->resource()->associate($this->morph);
        } else {
            $journal->detail = $this->detail;
        }
        $journal->save();
        // $journal->file = $this->file;
        if (! empty($this->file['id'])) {
            $file = File::query()->findOrFail($this->file['id']);
            $file->fileable()->associate($journal);
            $file->save();
        }

        $this->journal = $journal;
    }
}
