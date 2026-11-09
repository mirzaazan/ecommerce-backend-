<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Sales', 'Rs. '.number_format((float) Order::sum('total')))
                ->description('Revenue from all placed orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total Orders', (string) Order::count())
                ->description('Orders placed on the storefront')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
        ];
    }
}
