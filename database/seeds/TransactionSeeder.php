<?php

namespace Database\Seeders;

use App\Models\GiftCard;
use App\Services\Transactions\TransactionReturnProductService;
use Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\Rotation;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Database\Seeder;
use App\Services\Transactions\TransactionReturnService;
use App\Services\Transactions\TransactionCreationService;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = User::all();
        $products_all = Product::all();

        foreach ($users as $user) {
            $transactions = random_int(0, 6);

            for ($i = 0; $i <= $transactions; $i++) {
                $cashier = $users->shuffle()->whereIn('role_id', [1, 2])->first();
                Auth::login($cashier);

                if ($user->id === $cashier->id) {
                    continue;
                }

                $product_ids = $products_all->random(random_int(1, 5))->pluck('id');

                $products = [];
                foreach ($product_ids as $product_id) {
                    $products[] = [
                        'id' => $product_id,
                        'quantity' => random_int(1, 4),
                    ];
                }

                /** @var Rotation $rotation */
                $rotation = $user->rotations->random();

                if (random_int(0, 10) === 0) {
                    $giftCard = GiftCard::all()->random();
                    if (random_int(0, 5)) {
                        $giftCard->remaining_balance = Money::parse(random_int(0, 100_00));
                    }
                }

                $service = new TransactionCreationService(new Request([
                    'purchaser_id' => $user->id,
                    'cashier_id' => $cashier->id,
                    'rotation_id' => $rotation->id,
                    'products' => json_encode($products),
                    'created_at' => $rotation->start->addDays(random_int(1, 6))->addMillis(random_int(-99999, 99999)),
                    'gift_card_code' => $giftCard->code ?? null,
                ]), $user);

                if ($service->getResult() !== TransactionCreationService::RESULT_SUCCESS) {
                    continue;
                }

                $transaction = $service->getTransaction();

                if (random_int(0, 3) === 3) {
                    if (random_int(0, 1) === 1) {
                        (new TransactionReturnService($transaction))->return();
                    } else {
                        $product_id = $product_ids->random();
                        $max_to_return = 0;
                        foreach ($products as $product_info) {
                            if ($product_info['id'] === $product_id) {
                                $max_to_return = $product_info['quantity'];
                            }
                        }
                        $returning = random_int(0, $max_to_return);

                        for ($j = 0; $j <= $returning; $j++) {
                            $transactionProduct = $transaction->products->firstWhere('product_id', $product_id);
                            (new TransactionReturnProductService($transaction, $transactionProduct))->return();
                        }
                    }
                }
            }
        }
    }
}
