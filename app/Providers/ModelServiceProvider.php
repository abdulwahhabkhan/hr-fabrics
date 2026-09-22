<?php

namespace App\Providers;

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\StoreTransfer;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class ModelServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {

        $this->configureModels();
        $this->configureMorphs();
        $this->queryMacros();
    }

    private function configureModels(): void
    {
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
        if (app()->environment('local')) {
            Model::shouldBeStrict();
        }
    }

    private function configureMorphs(): void
    {
        Relation::enforceMorphMap([
            'account' => Account::class,
            'stock.grn' => FabricReceiving::class,
            'stock.po' => Purchase::class,
            'stock.por' => PurchaseReturn::class,
            'sales.so' => Order::class,
            'sales.sor' => SalesReturn::class,
            'journal' => Journal::class,
            'stock.transfer' => StoreTransfer::class,
        ]);
    }

    private function queryMacros(): void
    {
        Builder::macro('filterWhere', function (Expression|string $column, ?string $search) {
            if ($search === null or $search === '') {
                return $this;
            }

            return $this->where($column, '=', $search);

        });

        Builder::macro('filterStartWith', function (Expression|string $column, ?string $search) {
            if ($search === null || $search === '') {
                return $this;
            }
            $this->whereLike($column, "$search%");

            return $this;
        });

        Builder::macro('filterContain', function (Expression|string $column, ?string $search) {
            if ($search === null) {
                return $this;
            }
            $this->whereLike($column, "%$search%");

            return $this;
        });

        Builder::macro('filterDate', function (Expression|string $column, ?string $search) {
            if ($search === null) {
                return $this;
            }
            $this->whereDate($column, $search);

            return $this;
        });

    }
}
