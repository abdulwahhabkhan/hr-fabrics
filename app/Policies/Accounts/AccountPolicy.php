<?php

namespace App\Policies\Accounts;

use App\Models\Accounts\Account;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    /**
     * System accounts are managed by the application and cannot be edited.
     */
    public function update(User $user, Account $account): bool
    {
        return ! $account->system && hasPermission('accounts.accounts.update', $user);
    }

    /**
     * System accounts are managed by the application and cannot be deleted.
     */
    public function delete(User $user, Account $account): bool
    {
        return ! $account->system && hasPermission('accounts.accounts.destroy', $user);
    }
}
