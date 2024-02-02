<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use Cknow\Money\Money;
use App\Models\Product;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Settings;
use App\Models\UserLimits;
use App\Models\Transaction;
use App\Helpers\RotationHelper;
use App\Helpers\UserLimitsHelper;
use App\Http\Requests\UserRequest;
use App\Models\TransactionProduct;
use Database\Seeders\RotationSeeder;
use App\Services\Users\UserCreateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Activities\ActivityRegistrationCreateService;

// TODO: test with different limit durations (day/week)
// TODO: test when categories are made after user is made
// TODO: test after changing tax rates to ensure it is using historical data
class UserLimitsHelperTest extends TestCase
{
    use RefreshDatabase;

    public function testFindSpentCalculationIsCorrect(): void
    {
        [$user, $food_category, $merch_category, $activities_category, $waterfront_category] = $this->createFakeRecords();
        $user = $user->refresh();

        $food_limit_info = UserLimitsHelper::getInfo($user, $food_category->id);
        $food_category_spent = UserLimitsHelper::findSpent($user, $food_category->id, $food_limit_info);

        $this->assertEquals(Money::parse(12_09), $food_category_spent);

        $merch_limit_info = UserLimitsHelper::getInfo($user, $merch_category->id);
        $merch_category_spent = UserLimitsHelper::findSpent($user, $merch_category->id, $merch_limit_info);

        $this->assertEquals(Money::parse(60_54), $merch_category_spent);

        $activities_limit_info = UserLimitsHelper::getInfo($user, $activities_category->id);
        $activities_category_spent = UserLimitsHelper::findSpent($user, $activities_category->id, $activities_limit_info);

        $this->assertEquals(Money::parse(6_71), $activities_category_spent);

        $waterfront_limit_info = UserLimitsHelper::getInfo($user, $waterfront_category->id);
        $waterfront_category_spent = UserLimitsHelper::findSpent($user, $waterfront_category->id, $waterfront_limit_info);
        // Special case, they have no limit set for the waterfront category
        $this->assertEquals(Money::parse(0_00), $waterfront_category_spent);
    }

    public function testFindSpentCalculationIsCorrectAfterItemReturn(): void
    {
        $this->markTestIncomplete();
    }

    public function testFindSpentCalculationIsCorrectAfterTransactionReturn(): void
    {
        $this->markTestIncomplete();
    }

    public function testFindSpentCalculationIsCorrectAfterActivityReturn(): void
    {
        $this->markTestIncomplete();
    }

    public function testUserCanSpendUnlimitedInCategoryIfNegativeOneIsLimit(): void
    {
        [$user, , $merch_category] = $this->createFakeRecords();

        $can_spend_1_million_merch = UserLimitsHelper::canSpend($user, Money::parse(1000000_00), $merch_category->id);
        // This should be true as their merch category is unlimited
        $this->assertTrue($can_spend_1_million_merch);
    }

    public function testUserCannotSpendOverLimitInCategory(): void
    {
        [$user, $food_category, , $activities_category, $waterfront_category] = $this->createFakeRecords();
        $user = $user->refresh();

        $can_spend_1_dollar_food = UserLimitsHelper::canSpend($user, Money::parse(1_00), $food_category->id);
        // This should be true, as they've only spent 12.09 / 15.00 dollars, and another 1 dollar would not go past 15.
        $this->assertTrue($can_spend_1_dollar_food);

        $can_spend_12_dollars_food = UserLimitsHelper::canSpend($user, Money::parse(12_00), $food_category->id);
        // This should be false, as they've spent 12.09 / 15.00, and another 12 dollars would go past 15
        $this->assertFalse($can_spend_12_dollars_food);

        $can_spent_3_dollars_activities = UserLimitsHelper::canSpend($user, Money::parse(3_00), $activities_category->id);
        // This should be true, as they spent 6.29 / 10, and another 3 would not go over 10
        $this->assertTrue($can_spent_3_dollars_activities);

        $can_spent_5_dollars_activities = UserLimitsHelper::canSpend($user, Money::parse(5_00), $activities_category->id);
        // This should be false, as they spent 6.29 / 10, and another 5 would not go over 10
        $this->assertFalse($can_spent_5_dollars_activities);

        $can_spent_10_dollars_waterfront = UserLimitsHelper::canSpend($user, Money::parse(10_00), $waterfront_category->id);
        // This should be true, since they have no explicit limit set it defaults to unlimited
        $this->assertTrue($can_spent_10_dollars_waterfront);
    }

    public function testLimitsAndDurationsCorrectlyStoredIfValidFromUserRequest(): void
    {
        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        $this->actingAs($user);

        $candy_category = Category::factory()->create([
            'name' => 'Candy'
        ]);

        $merch_category = Category::factory()->create([
            'name' => 'Merch'
        ]);

        [$message, $result] = UserLimitsHelper::createOrEditFromRequest(new UserRequest([
            'limit' => [
                $merch_category->id => 25_00,
                $candy_category->id => 15_00
            ],
            'duration' => [
                $merch_category->id => UserLimits::LIMIT_DAILY,
                $candy_category->id => UserLimits::LIMIT_WEEKLY
            ]
        ]), $user, UserCreateService::class);

        $this->assertNull($message);
        $this->assertNull($result);

        $this->assertEquals(Money::parse(25_00), UserLimitsHelper::getInfo($user, $merch_category->id)->limit);
        $this->assertEquals(Money::parse(15_00), UserLimitsHelper::getInfo($user, $candy_category->id)->limit);

        $this->assertSame(UserLimits::LIMIT_DAILY, UserLimitsHelper::getInfo($user, $merch_category->id)->duration_int);
        $this->assertSame(UserLimits::LIMIT_WEEKLY, UserLimitsHelper::getInfo($user, $candy_category->id)->duration_int);
    }

    public function testInvalidLimitGivesErrorFromUserRequest(): void
    {
        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        $this->actingAs($user);

        $candy_category = Category::factory()->create([
            'name' => 'Candy'
        ]);

        [, $result] = UserLimitsHelper::createOrEditFromRequest(new UserRequest([
            'limit' => [
                $candy_category->id => -2_00
            ]
        ]), $user, UserCreateService::class);

        $this->assertSame(UserCreateService::RESULT_INVALID_LIMIT, $result);
    }

    public function testLimitOfZeroIsAllowed(): void
    {
        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        $this->actingAs($user);

        $candy_category = Category::factory()->create([
            'name' => 'Candy'
        ]);

        [$message, $result] = UserLimitsHelper::createOrEditFromRequest(new UserRequest([
            'limit' => [
                $candy_category->id => '0'
            ]
        ]), $user, UserCreateService::class);

        $this->assertNull($message);
        $this->assertNull($result);

        $this->assertEquals(Money::parse(0_00), UserLimitsHelper::getInfo($user, $candy_category->id)->limit);
    }

    public function testNoLimitProvidedDefaultsToNegativeOneFromUserRequest(): void
    {
        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        $this->actingAs($user);

        $candy_category = Category::factory()->create([
            'name' => 'Candy'
        ]);

        $merch_category = Category::factory()->create([
            'name' => 'Merch'
        ]);

        [$message, $result] = UserLimitsHelper::createOrEditFromRequest(new UserRequest([
            'limit' => [
                $merch_category->id => 25_00,
                $candy_category->id => null
            ]
        ]), $user, UserCreateService::class);

        $this->assertNull($message);
        $this->assertNull($result);

        $this->assertEquals(Money::parse(-1_00), UserLimitsHelper::getInfo($user, $candy_category->id)->limit);
    }

    public function testNoDurationProvidedDefaultsToDailyFromUserRequest(): void
    {
        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        $this->actingAs($user);

        $merch_category = Category::factory()->create([
            'name' => 'Merch'
        ]);

        [$message, $result] = UserLimitsHelper::createOrEditFromRequest(new UserRequest([
            'limit' => [
                $merch_category->id => 25_00,
            ]
        ]), $user, UserCreateService::class);

        $this->assertNull($message);
        $this->assertNull($result);

        $this->assertSame(UserLimits::LIMIT_DAILY, UserLimitsHelper::getInfo($user, $merch_category->id)->duration_int);
    }

    /** @return Role[] */
    private function createRoles(): array
    {
        $superadmin_role = Role::factory()->create();

        $camper_role = Role::factory()->create([
            'name' => 'Camper',
            'staff' => false,
            'superuser' => false,
            'order' => 2
        ]);

        return [$superadmin_role, $camper_role];
    }

    private function createSuperadminUser(Role $superadmin_role): User
    {
        return User::factory()->create([
            'role_id' => $superadmin_role->id
        ]);
    }

    /**
     * Creates the following records in db:
     * - Food category (5$ a day)
     * - Merch category (unlimited)
     * - Fake role for fake user
     * - Fake User
     * - UserLimits for the fake user for each category (one is unlimited, one is limited)
     * - Fake transactions for the fake user.
     */
    private function createFakeRecords(): array
    {
        app(RotationSeeder::class)->run();

        [$superadmin_role] = $this->createRoles();

        $user = $this->createSuperadminUser($superadmin_role);

        Settings::factory()->createMany([
            [
                'setting' => 'gst',
                'value' => '5.00',
            ],
            [
                'setting' => 'pst',
                'value' => '7.00',
            ]
        ]);

        [$food_category, $merch_category, $activities_category, $waterfront_category] = $this->createFakeCategories();

        UserLimits::factory()->create([
            'user_id' => $user->id,
            'category_id' => $food_category->id,
            'limit' => 15_00,
            'duration' => UserLimits::LIMIT_DAILY
        ]);

        UserLimits::factory()->create([
            'user_id' => $user->id,
            'category_id' => $merch_category->id,
            'limit' => -1_00
        ]);

        UserLimits::factory()->create([
            'user_id' => $user->id,
            'category_id' => $activities_category->id,
            'limit' => 10_00
        ]);

        [$skittles, $sweater, $coffee, $hat] = $this->createFakeProducts($food_category->id, $merch_category->id);
        [$widegame] = $this->createFakeActivities($activities_category);

        $transaction1 = Transaction::factory()->create([
            'purchaser_id' => $user->id,
            'cashier_id' => $user->id,
            'rotation_id' => resolve(RotationHelper::class)->getCurrentRotation()->id,
            'total_price' => 3_15, // TODO
            'purchaser_amount' => 3_15,
            'gift_card_amount' => 0_00,
        ]);

        $skittles_product = TransactionProduct::from($skittles, 2, 5);
        $skittles_product->transaction_id = $transaction1->id;
        $hat_product = TransactionProduct::from($hat, 1, 5);
        $hat_product->transaction_id = $transaction1->id;

        $transaction1->products()->saveMany([
            $skittles_product,
            $hat_product,
        ]);

        $transaction2 = Transaction::factory()->create([
            'purchaser_id' => $user->id,
            'cashier_id' => $user->id,
            'rotation_id' => resolve(RotationHelper::class)->getCurrentRotation()->id,
            'total_price' => 44_79, // TODO
            'purchaser_amount' => 44_79,
            'gift_card_amount' => 0_00,
        ]);

        $sweater_product = TransactionProduct::from($sweater, 1, 5, 7);
        $sweater_product->transaction_id = $transaction2->id;
        $coffee_product = TransactionProduct::from($coffee, 2, 5, 7);
        $coffee_product->transaction_id = $transaction2->id;

        $transaction2->products()->saveMany([
            $sweater_product,
            $coffee_product,
        ]);

        $this->actingAs($user);
        new ActivityRegistrationCreateService($widegame, $user);

        // TODO: General category with hat and widegame on it

        return [$user, $food_category, $merch_category, $activities_category, $waterfront_category];
    }

    /** @return Category[] */
    private function createFakeCategories(): array
    {
        $food_category = Category::factory()->create([
            'name' => 'Food'
        ]);

        $merch_category = Category::factory()->create([
            'name' => 'Merch'
        ]);

        $activities_category = Category::factory()->create([
            'name' => 'Activities',
            'type' => 3
        ]);

        $waterfront_category = Category::factory()->create([
            'name' => 'Waterfront'
        ]);

        return [$food_category, $merch_category, $activities_category, $waterfront_category];
    }

    /** @return Product[] */
    private function createFakeProducts($food_category_id, $merch_category_id): array
    {
        $skittles = Product::factory()->create([
            'name' => 'Skittles',
            'price' => 1_50,
            'pst' => false,
            'category_id' => $food_category_id
        ]);

        $sweater = Product::factory()->create([
            'name' => 'Sweater',
            'price' => 39_99,
            'pst' => true,
            'category_id' => $merch_category_id
        ]);

        $coffee = Product::factory()->create([
            'name' => 'Coffee',
            'price' => 3_99,
            'pst' => true,
            'category_id' => $food_category_id
        ]);

        $hat = Product::factory()->create([
            'name' => 'Hat',
            'price' => 15_00,
            'pst' => false,
            'category_id' => $merch_category_id
        ]);

        return [$skittles, $sweater, $coffee, $hat];
    }

    /** @return Activity[] */
    private function createFakeActivities($activities_category): array
    {
        $widegame = Activity::factory()->create([
            'name' => 'Widegame',
            'price' => 5_99,
            'pst' => true,
            'category_id' => $activities_category->id
        ]);

        return [$widegame];
    }
}
