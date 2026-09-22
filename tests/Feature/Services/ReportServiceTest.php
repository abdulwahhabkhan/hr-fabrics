<?php

use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Services\ReportService;

beforeEach(function () {
    $this->service = new ReportService;
    $this->start = today()->startOfDay();
    $this->end = today()->endOfDay();
});

it('includes only confirmed orders within the date range in the sales summary', function () {
    $inRange = Order::factory()->closed()->create(['transaction_date' => today()]);
    Order::factory()->create(['transaction_date' => today()]);
    Order::factory()->closed()->create(['transaction_date' => today()->subDays(5)]);

    $results = $this->service->getSalesSummary($this->start, $this->end)->pluck('id');

    expect($results)->toHaveCount(1)->toContain($inRange->id);
});

it('includes only confirmed sales returns within the date range in the return summary', function () {
    $inRange = SalesReturn::factory()->closed()->create(['transaction_date' => today()]);
    SalesReturn::factory()->create(['transaction_date' => today()]);

    $results = $this->service->getSaleReturnSummary($this->start, $this->end)->pluck('id');

    expect($results)->toHaveCount(1)->toContain($inRange->id);
});
