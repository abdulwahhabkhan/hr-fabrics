<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\AccountRequest;
use App\Models\Accounts\Account;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $accounts = Account::query()
            ->accounts()
            ->filterContain('name', $request->input('search'))
            ->orderby('updated_at', 'desc')->paginate()
            ->appends($request->only(['search']));

        return Inertia::render('Accounts/Accounts/AccountIndex',
            [
                'accounts' => $accounts,
                'filters' => $request->only('search'),
                'canAdd' => $request->user()
                    ->can('accounts.accounts.store'),
                'canUpdate' => $request->user()
                    ->can('accounts.accounts.update'),
                'canDelete' => $request->user()
                    ->can('accounts.accounts.destroy'),
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(AccountService $accountService): Response
    {
        return Inertia::render('Accounts/Accounts/AccountForm',
            [
                'account' => null,
                'expense_accounts' => $accountService->getExpenseAccounts(),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request): RedirectResponse
    {
        $request->validated();
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        Account::create($data);

        return Redirect::route('accounts.accounts.index')
            ->with(['success' => 'Account created Successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $account): Response
    {
        return Inertia::render('Accounts/Accounts/AccountForm',
            [
                'account' => $account,
                'expense_accounts' => new AccountService()->getExpenseAccounts(),
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();
        $account->update($data);

        return Redirect::route('accounts.accounts.index')
            ->with(['success' => 'Account updated Successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return Redirect::route('accounts.accounts.index')
            ->with(['success' => 'Account deleted Successfully']);
    }
}
