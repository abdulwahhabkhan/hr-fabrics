<?php

namespace App\Http\Controllers\Accounts;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\DirectoryType;
use App\Enums\EntryType;
use App\Enums\JournalHead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\JournalRequest;
use App\Http\Requests\Accounts\JournalSingleRequest;
use App\Http\Resources\Accounts\JournalResource;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Services\AccountService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->all();
        $query = Journal::query();
        $query->join(JournalDetail::tName(), Journal::qCol('id'), 'journal_id');
        $query->join(Account::tName(), Account::qCol('id'), 'account_id');
        $query->when($request['type'] ?? null, function ($query, $search) {
            $query->where('head', '=', $search);
            if ($search === 'journal') {
                $cashAccount = resolve(AccountService::class)->getCashAccount();
                $query->where(Account::qCol('id'), '!=', $cashAccount->id);
            }
        });

        $query->filterContain('reference_no', $request->input('reference_no'));
        $query->filterContain('name', $request->input('account'));
        $query->filterDate('posted_at', $request->input('date'));

        $query->select(
            [
                Journal::qCol('*'),
                JournalDetail::qCol('account_id'),
                JournalDetail::qCol('dr as debit'),
                JournalDetail::qCol('cr as credit'),
                Account::qCol('name'),
                Account::qCol('address->city as city', false),
            ]
        );
        $query->orderByDesc(Journal::qCol('created_at'));
        $vouchers = $query->paginate()->appends($filters);

        return Inertia::render(
            'Accounts/Journals/JournalIndex',
            [
                'vouchers' => JournalResource::collection($vouchers),
                'types' => ['journal', 'purchases', 'sales'],
                'canAdd' => $request->user()->can('accounts.journals.store'),
                'canAddSingle' => $request->user()->can('accounts.journals.single'),
                'canView' => $request->user()->can('accounts.journals.show'),
                'canDelete' => $request->user()->can('accounts.journals.destroy'),
                'filters' => $request->only(['type', 'reference_no', 'account']),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $accounts = Account::getAll();

        return Inertia::render(
            'Accounts/Journals/JournalForm',
            ['accounts' => $accounts, 'date' => today()->formDate(), 'morph_class' => Account::morphClass()]
        );
    }

    /**
     * Show the form for creating a new single resource.
     */
    public function single(): Response
    {
        $accounts = Account::query()->orderByName()->get();

        return Inertia::render(
            'Accounts/Journals/JournalSingleForm',
            [
                'accounts' => $accounts, 'morph_class' => Account::morphClass(),
                'directory' => DirectoryType::Vouchers->value,
                'date' => today()->formDate(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @throws Throwable
     */
    public function store(JournalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $transactionDate = Carbon::parse($data['date']);
        $fromAccount = $data['from_account']['id'];
        $toAccount = $data['to_account']['id'];
        resolve(LedgerEntry::class)
            ->setUser(auth()->user())
            ->setTransactionDate($transactionDate)
            ->setHead(JournalHead::Journal)
            ->setDetail($data['detail'])
            ->setFile($data['file'] ?? [])
            ->init()
            ->credit($fromAccount, $data['amount'])
            ->debit($toAccount, $data['amount'])
            ->logAction();

        return redirect()->route('accounts.journals.index');
    }

    /**
     * @throws Throwable
     */
    public function storeSingle(JournalSingleRequest $request)
    {
        $data = $request->validated();
        $head = JournalHead::Journal;
        $type = EntryType::tryFrom($data['type']);
        $transactionDate = Carbon::parse($data['date']);
        $accountId = $data['account']['id'];
        $amount = $data['amount'];
        resolve(LedgerEntry::class)
            ->setUser(auth()->user())
            ->setTransactionDate($transactionDate)
            ->setHead($head)
            ->setDetail($data['detail'])
            ->setFile($data['file'] ?? [])
            ->init()
            ->when($type === EntryType::Debit, function ($ledger) use ($amount, $accountId) {
                $cashAccount = resolve(AccountService::class)->getCashAccount();
                $ledger->debit($accountId, $amount);
                $ledger->credit($cashAccount->id, $amount);
            })
            ->when($type === EntryType::Credit, function ($ledger) use ($amount, $accountId) {
                $cashAccount = resolve(AccountService::class)->getCashAccount();
                $ledger->credit($accountId, $amount);
                $ledger->debit($cashAccount->id, $amount);
            })
            ->logAction();

        return Inertia::location(route('accounts.journals.single'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Journal $journal): Response
    {
        $transactions = $journal->transactions()->with(['account'])->get();
        $transaction = $transactions->first();
        $amount = $transaction->dr > 0 ? $transaction->dr : $transaction->cr;

        return Inertia::render(
            'Accounts/Journals/JournalView',
            [
                'journal' => $journal,
                'files' => $journal->files()->get(),
                'transactions' => $transactions,
                'amount' => $amount,
                'user' => $journal->user,
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws Throwable
     */
    public function destroy(Journal $journal): RedirectResponse
    {
        DB::transaction(function () use ($journal) {
            $transactions = $journal->transactions()->get();
            foreach ($transactions as $transaction) {
                $transaction->delete();
            }
            $journal->delete();
        });

        return Redirect::route('accounts.journals.index')
            ->with(['success' => 'Journal deleted Successfully']);
    }

    public function attachment(Journal $journal)
    {

        return response()->json([
            'files' => $journal->files()->get(), 'morph_class' => $journal->getMorphClass(),
            'directory' => DirectoryType::Vouchers->value, 'journal' => $journal,
        ]);
    }

    public function attachmentStore(Journal $journal, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required'],
            'file.file_name' => 'required',
        ]);
        unset($data['file']['file_thumbnail_url'], $data['file']['file_download_url']);
        $journal->file = $data['file'];
        $journal->update();

        return Redirect::route('accounts.journals.index')
            ->with(['success' => 'File uploaded successfully']);
    }

    public function accountBalance(int $accountId)
    {
        $data = resolve(AccountService::class)->getAccountBalanceDetailed($accountId);

        return response()->json($data);
    }
}
