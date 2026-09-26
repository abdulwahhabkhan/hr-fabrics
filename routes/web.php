<?php

use App\Http\Controllers\Accounts\AccountController;
use App\Http\Controllers\Accounts\BalanceHistoryController;
use App\Http\Controllers\Accounts\CashBankController;
use App\Http\Controllers\Accounts\IncomeStatementController;
use App\Http\Controllers\Accounts\JournalController;
use App\Http\Controllers\Accounts\LedgerController;
use App\Http\Controllers\Accounts\SalesSummaryController;
use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\FinishController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Purchases\FabricReceiving\UnLockFabricReceivingController;
use App\Http\Controllers\Purchases\FabricReceivingController;
use App\Http\Controllers\Purchases\FabricReceivingInventoryController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseInventoryController;
use App\Http\Controllers\Purchases\PurchaseLedgerController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Purchases\Return\ReturnInventoryController;
use App\Http\Controllers\Purchases\Return\ReturnLedgerController;
use App\Http\Controllers\Purchases\Return\UnlockReturnController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Reports\Products\ProductHistoryController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\Order\OrderController;
use App\Http\Controllers\Sales\Order\OrderInventoryController;
use App\Http\Controllers\Sales\Order\OrderLedgerController;
use App\Http\Controllers\Sales\Order\SalesReturnController;
use App\Http\Controllers\Settings\CityController;
use App\Http\Controllers\Settings\EmployeeController;
use App\Http\Controllers\Settings\JobController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Stock\BrandValuationController;
use App\Http\Controllers\Stock\ConversionController;
use App\Http\Controllers\Stock\InventoryController;
use App\Http\Controllers\Stock\StoreTransferController;
use App\Http\Controllers\Stock\StoreTransferInventoryController;
use App\Http\Controllers\Stock\StoreTransferLedgerController;
use App\Http\Controllers\Stock\ValueAdditionController;
use Illuminate\Support\Facades\Route;

Route::get('/show-date', function () {
    echo now()->toDateTimeString();
});

/* Route::inertia('/', 'welcome', ['environment' => app()->environment()])->name('home'); */

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::prefix('exceptions')->middleware('auth')->name('exceptions.')->group(function () {
    Route::get('/', [ExceptionController::class, 'index'])->name('home');
    Route::get('accounts', [ExceptionController::class, 'accounts'])->name('accounts');
    Route::get('accounts/detail', [ExceptionController::class, 'journalDetail'])
        ->name('accounts.detail');
    Route::get('stock', [ExceptionController::class, 'stockExceptions'])->name('stock');
    // Route::controller(ExceptionController::class)->group();
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::prefix('catalog')->middleware(['auth', 'verified', 'auth.role'])->name('catalog.')
    ->group(function () {
        Route::resource('brands', BrandController::class)->except(['show', 'create']);
        Route::resource('finish', FinishController::class)->except(['show', 'create']);
        Route::resource('products', ProductController::class)->except('show');
    });

/* Sales Routes */
Route::prefix('sales')->middleware(['auth', 'verified', 'auth.role'])->as('sales.')
    ->group(function () {
        // Customer Routes
        Route::resource('customers', CustomerController::class)->except(['destroy', 'show']);
        Route::post('customers/{customer}/suspend', [CustomerController::class, 'suspend'])
            ->name('customers.suspend')->withoutMiddleware(['auth.role']);
        Route::post('customers/{customer}/activate', [CustomerController::class, 'activate'])
            ->name('customers.activate')->withoutMiddleware(['auth.role']);
        Route::resource('orders', OrderController::class)->except('destroy');
        Route::get('orders/{order}/gate-pass', [OrderController::class, 'gatePass'])
            ->name('orders.gate-pass');
        Route::get('orders/{order}/ledger', OrderLedgerController::class)
            ->name('orders.ledger');
        Route::get('orders/{order}/inventory', OrderInventoryController::class)
            ->name('orders.inventory');
        Route::get('orders/{order}/bilti', [OrderController::class, 'bilti'])
            ->name('order.bilti')->withoutMiddleware(['auth.role']);
        Route::post('orders/{order}/bilti-upload/{file}', [
            OrderController::class,
            'biltiUpload',
        ])
            ->name('order.bilti.create')->withoutMiddleware(['auth.role']);
        Route::resource('returns', SalesReturnController::class);
        Route::get('returns/{return}/ledger', App\Http\Controllers\Sales\Return\ReturnLedgerController::class)
            ->name('returns.ledger');
        Route::get('returns/{return}/inventory', App\Http\Controllers\Sales\Return\ReturnInventoryController::class)
            ->name('returns.inventory');

        // Route::get('sales-by-suit', SalesBySuitController::class)->name('sales-by-suit');
    });

/* Purchase Routes */
Route::prefix('purchases')->as('purchases.')->middleware([
    'auth',
    'verified',
    'auth.role',
])
    ->group(function () {
        // Supplier Routes
        Route::resource('suppliers', SupplierController::class)->only([
            'index',
            'edit',
            'update',
            'store',
        ]);
        Route::resource('pos', PurchaseController::class);
        Route::get('pos/{receipt}/inventory', PurchaseInventoryController::class)
            ->name('pos.inventory');
        Route::get('pos/{receipt}/ledger', PurchaseLedgerController::class)
            ->name('pos.ledger');
        Route::resource('fabric-receivings', FabricReceivingController::class);
        Route::get('fabric-receivings/{fabric_receiving}/inventory', FabricReceivingInventoryController::class)
            ->name('fabric-receivings.inventory');
        Route::post('fabric-receivings/{fabric_receiving}/unlock', UnLockFabricReceivingController::class)
            ->name('fabric-receivings.unlock');
        Route::resource('por', PurchaseReturnController::class)->except(['destroy']);
        Route::get('por/{return}/ledger', ReturnLedgerController::class)
            ->name('por.ledger');
        Route::get('por/{return}/inventory', ReturnInventoryController::class)
            ->name('por.inventory');

        Route::post('por/{return}/unlock', UnlockReturnController::class)
            ->name('por.unlock')
            ->withoutMiddleware('auth.role');
    });

/* Purchase Routes */
Route::prefix('stocks')->middleware(['auth', 'verified', 'auth.role'])->as('stocks.')
    ->group(function () {
        Route::resource('store-transfers', StoreTransferController::class)->except('destroy');
        Route::get('store-transfers/{store_transfer}/ledger', StoreTransferLedgerController::class)
            ->name('store-transfers.ledger');
        Route::get('store-transfers/{store_transfer}/inventory', StoreTransferInventoryController::class)
            ->name('store-transfers.inventory');
        Route::withoutMiddleware('auth.role')
            ->group(function () {
                Route::get('stock/product', [InventoryController::class, 'productStock'])
                    ->name('stock.product');

                // Store Transfer Item
                Route::get('store-transfers/items/{storeTransfer}', [
                    StoreTransferController::class,
                    'storeTransferItems',
                ])
                    ->name('store-transfers.items')->where(['storeTransfer' => '[0-9]+']);
                Route::post('store-transfers/items/{storeTransfer}', [
                    StoreTransferController::class,
                    'storeTransferItem',
                ])
                    ->name('store-transfers.item')->where(['storeTransfer' => '[0-9]+']);
                Route::delete('store-transfers/items/{id}', [
                    StoreTransferController::class,
                    'deleteStoreTransferItem',
                ])
                    ->name('store-transfers.item.destroy')->where(['id' => '[0-9]+']);
                Route::get('store-transfers/stock/{storeTransfer}', [
                    StoreTransferController::class,
                    'availableStock',
                ])
                    ->name('store-transfers.stock')->where(['storeTransfer' => '[0-9]+']);
                Route::post('store-transfers/items/{storeTransfer}/bulk', [
                    StoreTransferController::class,
                    'storeTransferItemsBulk',
                ])
                    ->name('store-transfers.items.bulk')->where(['storeTransfer' => '[0-9]+']);
                //
            });
        Route::resource('conversions', ConversionController::class)
            ->except('show', 'edit', 'update');
        Route::resource('inventories', InventoryController::class)->only('index');
        Route::get('value-by-brand', BrandValuationController::class)
            ->name('value-by-brand');
        Route::get('product-history', ProductHistoryController::class)
            ->name('product-history');
        Route::resource('value-addition', ValueAdditionController::class)->only('index', 'create');
    });

/* Account Routes */
Route::prefix('accounts')->middleware(['auth', 'verified', 'auth.role'])
    ->name('accounts.')->group(function () {
        Route::get('ledgers', [LedgerController::class, 'index'])->name('ledgers.index');
        Route::get('ledgers/{account}', [LedgerController::class, 'show'])
            ->name('ledgers.show')->where(['account' => '[0-9]+']);
        Route::resource('accounts', AccountController::class)->except('show');
        Route::get('income-statement', [IncomeStatementController::class, 'index'])
            ->name('income-statement');
        Route::get('income-statement/detail', [IncomeStatementController::class, 'detail'])
            ->name('income-statement.detail');
        Route::get('balance-history', [BalanceHistoryController::class, '__invoke'])
            ->name('balance-history');
        Route::get('sale-summary', SalesSummaryController::class)
            ->name('sale-summary');
        Route::post('journals/single', [JournalController::class, 'storeSingle'])
            ->name('journals.single-store');
        Route::get('journals/single', [JournalController::class, 'single'])
            ->name('journals.single');
        Route::get('{account}/balance', [JournalController::class, 'accountBalance'])->name('accounts.balance');
        Route::get('journals/{journal}/attachment', [
            JournalController::class,
            'attachment',
        ])
            ->name('journals.attachment');
        Route::post('journals/{journal}/attachment/store', [
            JournalController::class,
            'attachmentStore',
        ])
            ->name('journals.attachment.store');
        Route::resource('journals', JournalController::class)->except(['edit', 'update']);

        Route::get('cash-bank/summary', [CashBankController::class, 'index'])->name('cash-bank.summary');
        Route::get('cash-bank/detail', [CashBankController::class, 'detail'])->name('cash-bank.detail');
    });

// Setting Routes
Route::prefix('settings')->middleware(['auth', 'verified', 'auth.role'])
    ->name('settings.')->group(function () {
        Route::resource('roles', RoleController::class, ['module' => 'Role'])->except(['show', 'destroy']);
        Route::resource('users', UserController::class, ['module' => 'User'])->except('show');
        Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{id}', [JobController::class, 'index'])->name('jobs.show');

        Route::resource('cities', CityController::class, ['module' => 'City'])->except('show');
        Route::resource('employees', EmployeeController::class, ['module' => 'Employee'])
            ->only(['index', 'destroy']);
    });

// User Profile
Route::middleware(['auth'])->group(function () {
    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('profile', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');
});

require __DIR__.'/auth.php';
require __DIR__.'/reports.php';
require __DIR__.'/auto-complete.php';
require __DIR__.'/inbound.php';
require __DIR__.'/ajax.php';
require __DIR__.'/actions.php';
require __DIR__.'/file.php';
