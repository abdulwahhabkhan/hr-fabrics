<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Inertia\Inertia;

class BankBookController extends Controller
{
    public function __invoke(AccountService $service)
    {
        return Inertia::render('Reports/Accounts/BankReport', $service->bankBookSummary(now()->startOfDay()));
    }
}
