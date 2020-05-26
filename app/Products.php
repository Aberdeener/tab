<?php

namespace App;

use App\Http\Controllers\OrderController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Products extends Model
{
    use QueryCacheable;
    
    protected $cacheFor = 180;

    public static function findSold($product, $lookBack) {

        $sold = 0;

        foreach (Transactions::where('created_at', '>=', Carbon::now()->subDays($lookBack)->toDateTimeString())->get() as $transaction) {
            foreach(explode(", ", $transaction->products) as $transaction_product) {
               if (OrderController::deserializeProduct($transaction_product)['id'] == $product) $sold++;
            }
        }

        return $sold;
    }
}
