<?php

use App\Models\Sales\OrderItem;

test('total amount casts to floor integer', function () {
    $item = new OrderItem;

    $item->total_amount = 123.99;
    expect($item->total_amount)->toEqual(123);

    $item->total_amount = 123.01;
    expect($item->total_amount)->toEqual(123);
});

test('total commission casts to floor integer', function () {
    $item = new OrderItem;

    $item->total_commission = 45.99;
    expect($item->total_commission)->toEqual(45.99);

    $item->total_commission = 45.01;
    expect($item->total_commission)->toEqual(45.01);
});
