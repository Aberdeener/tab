<?php

namespace App\Models;

use Cknow\Money\Money;
use App\Helpers\TaxHelper;
use Cknow\Money\Casts\MoneyIntegerCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    public const STATUS_NOT_RETURNED = 0;

    public const STATUS_PARTIAL_RETURNED = 1;

    public const STATUS_FULLY_RETURNED = 2;

    use HasFactory;

    protected $casts = [
        'total_price' => MoneyIntegerCast::class,
        'purchaser_amount' => MoneyIntegerCast::class,
        'gift_card_amount' => MoneyIntegerCast::class,
    ];

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rotation(): BelongsTo
    {
        return $this->belongsTo(Rotation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(TransactionProduct::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function getReturnedTotal(): Money
    {
        if ($this->isReturned()) {
            return $this->total_price;
        }

        if ($this->status === self::STATUS_NOT_RETURNED) {
            return Money::parse(0);
        }

        return $this->products
            ->where('returned', '>=', 1)
            ->reduce(function (Money $carry, TransactionProduct $product) {
                return $carry->add(TaxHelper::forTransactionProduct($product, $product->returned));
            }, Money::parse(0));
    }

    public function getOwingTotal(): Money
    {
        return $this->total_price->subtract($this->getReturnedTotal());
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_FULLY_RETURNED;
    }

    public function getStatusHtml(): string
    {
        return match ($this->status) {
            self::STATUS_FULLY_RETURNED => '<span class="tag is-medium">🚨 Returned</span>',
            self::STATUS_PARTIAL_RETURNED => '<span class="tag is-medium">⚠️ Semi Returned</span>',
            self::STATUS_NOT_RETURNED => '<span class="tag is-medium">👌 Not Returned</span>',
        };
    }
}
