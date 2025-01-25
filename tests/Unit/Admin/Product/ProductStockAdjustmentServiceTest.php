<?php

namespace Tests\Unit\Admin\Product;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\ProductStockAdjustmentRequest;
use App\Services\Products\ProductStockAdjustmentService;

class ProductStockAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCannotAdjustStockWithZeroInput(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $productService = new ProductStockAdjustmentService(new ProductStockAdjustmentRequest([
            'product_id' => $product->id,
            'adjust_stock' => 0,
        ]), $product);

        $this->assertSame(ProductStockAdjustmentService::RESULT_INPUT_ZERO, $productService->getResult());
    }

    public function testCanAdjustStockNormally(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock' => 0,
        ]);

        $productService = new ProductStockAdjustmentService(new ProductStockAdjustmentRequest([
            'product_id' => $product->id,
            'adjust_stock' => 7,
        ]), $product);

        $this->assertSame(ProductStockAdjustmentService::RESULT_SUCCESS, $productService->getResult());

        $product->refresh();
        $this->assertSame(7, $product->stock);
        $this->assertSame($product->id, session()->get('last_product')->id);
    }
}
