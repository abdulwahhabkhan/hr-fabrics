<?php

declare(strict_types=1);

use App\Models\Sales\Order;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

/**
 * Confirmed order with a fixed net total on the given day (the factory recalculates totals from items).
 */
function confirmedOrder(int $netTotal, DateTimeInterface $day): Order
{
    $order = Order::factory()->closed()->create();
    $order->forceFill(['net_total' => $netTotal, 'transaction_date' => $day])->save();

    return $order;
}

it('shows today\'s totals, yesterday comparison and invoice count', function (): void {
    confirmedOrder(1000, today());
    confirmedOrder(500, today());
    confirmedOrder(800, today()->subDay());
    Order::factory()->create();

    $this->actingAs($this->getAdmin())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->missing('stats')
            ->has('recent_orders', 4)
            ->loadDeferredProps('stats', fn (Assert $reload) => $reload
                ->where('stats.sales.net', 1500)
                ->where('stats.sales.yesterday', 800)
                ->where('stats.sales.invoices', 2)
            )
        );
});

it('returns a zero-filled 14 day sales trend ending today', function (): void {
    confirmedOrder(1200, today());
    confirmedOrder(300, today()->subDays(3));
    confirmedOrder(9999, today()->subDays(20));

    $this->actingAs($this->getAdmin())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('trend')
            ->loadDeferredProps('trend', fn (Assert $reload) => $reload
                ->has('trend', 14)
                ->where('trend.13', ['date' => today()->toDateString(), 'total' => 1200])
                ->where('trend.10', ['date' => today()->subDays(3)->toDateString(), 'total' => 300])
                ->where('trend.0', ['date' => today()->subDays(13)->toDateString(), 'total' => 0])
            )
        );
});
