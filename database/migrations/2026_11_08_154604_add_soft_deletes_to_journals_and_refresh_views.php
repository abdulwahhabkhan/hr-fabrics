<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('journal_details', function (Blueprint $table) {
            $table->softDeletes();
        });

        $this->refreshViews();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('journal_details')->whereNotNull('deleted_at')->delete();
        DB::table('journals')->whereNotNull('deleted_at')->delete();

        Schema::table('journal_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        $this->refreshViews();
    }

    private function refreshViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS journal_ledgers;');
        DB::statement('DROP VIEW IF EXISTS account_summary;');
        DB::statement($this->journalLedgersView());
        DB::statement($this->accountSummaryView());
    }

    private function journalLedgersView(): string
    {
        $query = DB::table(JournalDetail::tName())
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
            ->whereNull(Journal::qCol('deleted_at'))
            ->whereNull(JournalDetail::qCol('deleted_at'));

        return 'CREATE VIEW journal_ledgers AS '.$query->toSql();
    }

    private function accountSummaryView(): string
    {
        $ledgers = DB::table(JournalDetail::tName())
            ->select('account_id', DB::raw('sum(dr) as total_dr'), DB::raw('sum(cr) as total_cr'))
            ->whereNull('deleted_at')
            ->whereIn('journal_id', fn ($query) => $query->select('id')->from(Journal::tName())->whereNull('deleted_at'))
            ->groupBy('account_id');

        $query = Account::query()
            ->select('id', 'name', 'type', 'address->city as city', 'total_dr', 'total_cr')
            ->leftJoinSub($ledgers, 'ledger', function ($join) {
                $join->on('accounts.id', '=', 'account_id');
            });

        return 'CREATE VIEW account_summary AS '.$query->toSql();
    }
};
