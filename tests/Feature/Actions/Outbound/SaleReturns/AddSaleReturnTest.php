<?php

use App\Actions\Outbound\SaleReturns\AddSaleReturn;
use App\Models\Sales\SalesReturn;

use function Pest\Laravel\assertDatabaseCount;

test('add sale return is a no-op stub', function () {
    resolve(AddSaleReturn::class)->handle();

    assertDatabaseCount(SalesReturn::class, 0);
});
