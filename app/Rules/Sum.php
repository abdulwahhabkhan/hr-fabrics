<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Sum implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(public int $total)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = array_values($value);
        if (array_sum($values) > $this->total) {
            $fail('The :attribute sum must be equal to '.$this->total.'.');
        }
    }
}
