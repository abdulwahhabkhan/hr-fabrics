<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotInPasswordHistory implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    /**
     * Fail when the value matches the current password or a recent previous one.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user->hasUsedPassword((string) $value)) {
            $fail(__('You cannot reuse one of your last :count passwords.', [
                'count' => max(1, (int) config('password-policy.history_count')),
            ]));
        }
    }
}
