<?php

namespace App\Http\Controllers;

use App\Charts\StatisticsChart;
use App\Products;
use App\Transactions;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class StatisticsChartController extends Controller
{
    // $lookBack -> n => Last n days
    public static function orderInfo($lookBack)
    {
        $recentorders = new StatisticsChart;

        $normal_data = Transactions::where([['created_at', '>=', Carbon::now()->subDays($lookBack)->toDateTimeString()], ['status', 0]])->selectRaw('COUNT(*) AS count, DATE(created_at) date')->groupBy('date')->get();
        $returned_data = Transactions::where([['created_at', '>=', Carbon::now()->subDays($lookBack)->toDateTimeString()], ['status', 1]])->selectRaw('COUNT(*) AS count, DATE(created_at) date')->groupBy('date')->get();

        $normal_orders = array();
        $returned_orders = array();
        $labels = array();
        
        foreach (CarbonPeriod::create(Carbon::now()->subDays($lookBack), Carbon::now())->toArray() as $day) {
            foreach ($normal_data as $row) {
                if ($row['date'] == $day->toDateString()) {
                    if (!in_array($row['date'], $labels)) array_push($labels, $row['date']);
                    array_push($normal_orders, $row['count']);
                }
                $found = false;
                foreach ($returned_data as $row1) {
                    if ($row['date'] == $row1['date']) {
                        $found = true;
                        array_push($returned_orders, $row1['count']);
                        break;
                    }
                }
                if (!$found) array_push($returned_orders, 0);
            }
        }
        $recentorders->labels($labels);
        $recentorders->dataset('Normal Orders', 'line', $normal_orders)->fill(true)->lineTension(0)->color("rgb(72, 187, 120)");
        $recentorders->dataset('Returned Orders', 'line', $returned_orders)->fill(true)->lineTension(0)->color("rgb(245, 101, 101)");
        return $recentorders;
    }

    public static function itemInfo($lookBack)
    {
        $popularitems = new StatisticsChart;

        $sales = array();

        foreach (Products::all() as $product) {
            $sold = Products::findSold($product->id, $lookBack);
            if ($sold < 1) continue;
            array_push($sales, ['name' => $product->name, 'sold' => $sold]);
        }

        uasort($sales, function ($a, $b) {
            return ($a['sold'] > $b['sold'] ? -1 : 1);
        });

        $popularitems->labels(array_column($sales, 'name'));
        $popularitems->dataset('Sold', 'bar', array_column($sales, 'sold'))->color("rgb(72, 187, 120)");
        return $popularitems;
    }
}
