<?php

namespace App\Models;

use Cknow\Money\Money;
use App\Helpers\TaxHelper;
use Cknow\Money\Casts\MoneyIntegerCast;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Impersonate;

    protected $fillable = [
        'full_name',
        'username',
        'balance',
        'password',
        'role_id'
    ];

    protected $casts = [
        'full_name' => 'string',
        'username' => 'string',
        'balance' => MoneyIntegerCast::class,
    ];

    protected $with = [
        // 'userLimits',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'purchaser_id')->with('products');
    }

    public function activityRegistrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function rotations(): BelongsToMany
    {
        // TODO why is this distinct?
        return $this->belongsToMany(Rotation::class)->distinct();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function userLimits(): HasMany
    {
        return $this->hasMany(UserLimit::class);
    }

    public function limitFor(Category $category): UserLimit
    {
        $userLimit = $this->userLimits->where('category_id', $category->id)->first();
        if ($userLimit) {
            return $userLimit;
        }

        return UserLimit::create([
            'user_id' => $this->id,
            'category_id' => $category->id,
            'limit' => Money::parse(-1_00),
            'duration' => UserLimit::LIMIT_DAILY,
        ]);
    }

    public function hasPermission($permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Find how much a user has spent in total.
     * Does not factor in returned items/orders.
     */
    public function findSpent(): Money
    {
        return Money::parse(0)
            ->add(...$this->transactions->map->total_price)
            ->add(...$this->activityRegistrations->map->total_price);
    }

    /**
     * Find how much a user has returned in total.
     * This will see if a whole order has been returned, or if not, check all items in an unreturned order.
     */
    public function findReturned(): Money
    {
        $returned = Money::parse(0);

        $this->transactions->each(function (Transaction $transaction) use (&$returned) {
            if ($transaction->isReturned()) {
                $returned = $returned->add($transaction->total_price);
                return;
            }

            if ($transaction->status === Transaction::STATUS_NOT_RETURNED) {
                return;
            }

            foreach ($transaction->products->where('returned', '>', 0) as $product) {
                $returned = $returned->add(TaxHelper::forTransactionProduct($product, $product->returned));
            }
        });

        $returned = $returned->add(
            ...$this->activityRegistrations
                ->where('returned', true)
                ->map->total_price
        );

        return $returned;
    }

    /**
     * Find how much money a user owes.
     * Taking their amount spent and subtracting the amount they have returned and sum of their payouts.
     */
    public function findOwing(): Money
    {
        return $this->findSpent()
            ->subtract($this->findReturned())
            ->subtract($this->findPaidOut());
    }

    public function findPaidOut(): Money
    {
        return Money::sum(Money::parse(0), ...$this->payouts->map->amount);
    }

    public function canImpersonate()
    {
        return $this->role->superuser;
    }

    public function canBeImpersonated()
    {
        return $this->role->staff;
    }
}
