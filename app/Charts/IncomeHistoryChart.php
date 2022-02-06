<?php

namespace App\Charts;

use App\Helpers\RotationHelper;
use App\Models\Transaction;
use Chartisan\PHP\Chartisan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IncomeHistoryChart
{
    public ?array $middlewares = [
        'auth',
    ]; // TODO: use HasPermission::class middleware, just dont know how to pass the permission

    public function handler(Request $request): Chartisan
    {
        $stats_rotation_id = resolve(RotationHelper::class)->getCurrentRotation()->id;

        $normal_data = Transaction::query()
                            ->where([['rotation_id', $stats_rotation_id], ['returned', false]])
                            ->selectRaw('DATE(created_at) date, ROUND(SUM(total_price), 2) total_price')
                            ->groupBy('date')
                            ->get();
        $returned_data = Transaction::query()
                            ->where([['rotation_id', $stats_rotation_id], ['returned', true]])
                            ->selectRaw('DATE(created_at) date, ROUND(SUM(total_price), 2) total_price')
                            ->groupBy('date')
                            ->get();

        $normal_orders = $returned_orders = $labels = [];

        foreach ($normal_data as $normal_order) {
            $labels[] = Carbon::parse($normal_order['date'])->format('M jS Y');
            $normal_orders[] = $normal_order['total_price'];
            $found = false;

            foreach ($returned_data as $returned_order) {
                if ($normal_order['date'] === $returned_order['date']) {
                    $found = true;
                    $returned_orders[] = $returned_order['total_price'];
                    break;
                }
            }

            if (!$found) {
                $returned_orders[] = 0;
            }
        }

        return Chartisan::build()
            ->labels($labels)
            ->dataset('Returned', $returned_orders)
            ->dataset('Income', $normal_orders);
    }
}