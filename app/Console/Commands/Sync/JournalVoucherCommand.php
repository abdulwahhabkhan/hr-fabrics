<?php

namespace App\Console\Commands\Sync;

use App\Models\Account\JournalBook;
use App\Models\Accounts\Journal;
use Illuminate\Console\Command;

class JournalVoucherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:journal.voucher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from journal vouchers to Journals';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->replayJournalBook();

        return 0;
    }

    private function replayJournalBook()
    {
        $journalBooks = JournalBook::with('user')->get();
        foreach ($journalBooks as $journalBook) {
            $type = $journalBook->debit > 0 ? 'debit' : 'credit';
            $amount = $journalBook->debit > 0 ? $journalBook->debit : $journalBook->credit;

            $journal = $journalBook->journal()->first();
            if ($journal) {
                $journal->transactions()->delete();
                $journal->post($journalBook->account_id, $journalBook->debit, $journalBook->credit);
            } else {
                $data = [
                    'date' => $journalBook->created_at,
                    'account' => ['id' => $journalBook->account_id],
                    'type' => $type,
                    'detail' => $journalBook->desc,
                    'amount' => $amount,
                    'user' => $journalBook->user,
                    'user_id' => $journalBook->created_by,
                ];

                Journal::postJournalSingle($data, $journalBook);
            }
        }
    }
}
