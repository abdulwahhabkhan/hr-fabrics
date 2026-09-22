<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private string $view = 'journal_ledgers';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();
        DB::statement($this->createView());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS {$this->view};");
    }

    private function createView(): string
    {
        $sql = JournalDetail::query()
            ->select([
                Journal::qCol('id as id'),
                'account_id',
                Journal::qCol('resource_type'),
                Journal::qCol('resource_id'),
                'head',
                'name',
                'name_urdu',
                'type',
                'suspended as is_suspended',
                'limit as credit_limit',
                'address->city as city',
                'address->region as region',
                'detail',
                'posted_at',
                'dr',
                'cr',
            ])
            ->selectRaw('(dr - cr) as total')
            ->join(Journal::tName(), Journal::qCol('id'), '=', 'journal_id')
            ->join(Account::tName(), Account::qCol('id'), '=', 'account_id')
            ->toSql();

        return <<<EOD
        CREATE VIEW {$this->view} AS
            $sql
        EOD;
    }
};
