<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\JournalDetail;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private string $view = 'account_summary';

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
        $ledgers = JournalDetail::query()
            ->select(
                'account_id',
                DB::raw('sum(dr) as total_dr'),
                DB::raw('sum(cr) as total_cr')
            )
            ->groupBy('account_id');
        $sql = Account::query()
            ->select('id', 'name', 'type', 'address->city as city', 'total_dr', 'total_cr')
            ->leftJoinSub($ledgers, 'ledger', function ($join) {
                $join->on('accounts.id', '=', 'account_id');
            })->toSql();

        return <<<EOD
        CREATE VIEW {$this->view} AS
            $sql
        EOD;
    }
};
