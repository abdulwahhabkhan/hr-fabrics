<?php

namespace App\Console\Commands\Widgets;

use App\Enums\PackingType;
use App\Models\Sales\OrderItem;
use App\Models\Widget;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SalesWidgetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'widgets:sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate data for sales widget in dashboard';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sales Widget processing start');
        $this->processWidget();
        $this->info('Sales Widget processing end');

        return CommandAlias::SUCCESS;
    }

    private function processWidget(): void
    {
        $group = Widget::DASHBOARD_GROUP;
        $name = Widget::WIDGET_SALE;
        $itemsTotal = OrderItem::query()
            ->selectRaw('SUM(total_amount) AS total')
            ->selectRaw('SUM(total_qty) AS meter')
            ->selectRaw('SUM(IF(unit=? , qty, 0)) AS box', [PackingType::Box->value])
            ->selectRaw('SUM(IF(unit=? , qty, 0)) AS thaan', [PackingType::Thaan->value])
            ->selectRaw('SUM(IF(unit=? , qty, 0)) AS suit', [PackingType::Suit->value])
            ->where('created_at', '>', today()->toDateTimeString())
            ->first();

        Widget::query()
            ->updateOrCreate(['group' => $group, 'name' => $name], ['data' => $itemsTotal]);
    }
}
