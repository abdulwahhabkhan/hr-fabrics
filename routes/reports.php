<?php

use App\Http\Controllers\Reports\AccountReportController;
use App\Http\Controllers\Reports\AttendanceRegisterController;
use App\Http\Controllers\Reports\AttendanceReportController;
use App\Http\Controllers\Reports\BankBookController;
use App\Http\Controllers\Reports\CashCreditSaleController;
use App\Http\Controllers\Reports\CustomerBalanceController;
use App\Http\Controllers\Reports\CustomerLastPaymentController;
use App\Http\Controllers\Reports\DailyExpenseController;
use App\Http\Controllers\Reports\DailyPurchasesController;
use App\Http\Controllers\Reports\DailySummaryController;
use App\Http\Controllers\Reports\DailySummaryReportController;
use App\Http\Controllers\Reports\InOutTransactionDetailController;
use App\Http\Controllers\Reports\InOutTransactionSummaryController;
use App\Http\Controllers\Reports\JournalReportController;
use App\Http\Controllers\Reports\POReportController;
use App\Http\Controllers\Reports\Products\FastSellingProductController;
use App\Http\Controllers\Reports\Products\ProductByPurchaseDateController;
use App\Http\Controllers\Reports\Sales\DailyAverageController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->middleware(['auth', 'verified', 'auth.role'])
    ->as('reports.')
    ->group(function () {
        Route::get('po/purchases', [POReportController::class, 'purchases'])
            ->name('po.purchases');

        Route::get('accounts', [AccountReportController::class, 'index'])
            ->name('account-report.index');

        Route::get('fast-selling/products', FastSellingProductController::class)
            ->name('fast-selling.products');

        Route::get('accounts/expenses', DailyExpenseController::class)
            ->name('account-report.expenses');

        Route::get('products/purchased-date', ProductByPurchaseDateController::class)
            ->name('purchased-date.products');

        Route::get('accounts/receivables',
            [AccountReportController::class, 'receivables'])
            ->name('account-report.receivables');

        Route::get('accounts/receivables-by-city',
            [AccountReportController::class, 'receivablesByCity'])
            ->name('account-report.city-by-receivables');

        Route::get('accounts/book-book', BankBookController::class)
            ->name('account-report.bank-book');

        Route::get('daily-journal', [JournalReportController::class, 'index'])
            ->name('journal-report.index');

        Route::get('sales/daily', [DailySummaryReportController::class, 'dailySales'])
            ->name('sales-daily');

        Route::get('sales/sale-cash-credit', CashCreditSaleController::class)
            ->name('sale-cash-credit');

        Route::get('purchases/daily', DailyPurchasesController::class)
            ->name('purchases_daily');

        Route::get('summary', DailySummaryController::class)->name('summary');
        Route::get('in-out-transactions-summary',
            InOutTransactionSummaryController::class)->name('in.out.transactions.summary');
        Route::get('in-out-transactions-summary/detail',
            InOutTransactionDetailController::class)->name('in.out.transactions.detail');
        Route::get('sales/daily-average', DailyAverageController::class)->name('sales-daily-average');
        Route::get('customers/last-payment', CustomerLastPaymentController::class)
            ->name('customers.last-payment');
        Route::get('customer/balance', CustomerBalanceController::class)->name('customers.balance');

        Route::get('attendance', [AttendanceReportController::class, 'index'])
            ->name('attendance.index');
        Route::get('attendance/detail', [AttendanceReportController::class, 'detail'])
            ->name('attendance.detail');
        Route::get('attendance/register', [AttendanceRegisterController::class, 'index'])
            ->name('attendance.register');
        Route::get('attendance/register/detail', [AttendanceRegisterController::class, 'detail'])
            ->name('attendance.register.detail');
    });

Route::prefix('widgets')
    ->middleware(['auth', 'verified', 'auth.role'])
    ->as('widgets.')
    ->group(function () {
        Route::controller(WidgetController::class)->group(function () {
            Route::get('average-sale-meter', 'avgSalesMeter')->name('average-sale-meter');
        });
    });
