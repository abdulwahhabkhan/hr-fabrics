<?php

namespace App\Models\View;

use App\Models\Model;
use App\Models\Traits\AsView;

abstract class AccountSummary extends Model
{
    use AsView;

    protected $table = 'account_summary';
}
