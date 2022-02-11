<?php

namespace Tests\Feature\Category;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\FormRequestTestCase;

class CategoryRequestTest extends FormRequestTestCase
{
    use RefreshDatabase;

    public function testNameIsRequiredAndHasMinAndMaxAndUnique(): void
    {
        $this->assertHasErrors('name', new CategoryRequest([
            'name' => null,
        ]));

        $this->assertHasErrors('name', new CategoryRequest([
            'name' => '1',
        ]));

        $this->assertHasErrors('name', new CategoryRequest([
            'name' => 'looooooooooooooooooooooooooooong name',
        ]));

        $this->assertNotHaveErrors('name', new CategoryRequest([
            'name' => 'name :D',
        ]));

        $category = Category::factory()->create();

        $this->assertHasErrors('name', new CategoryRequest([
            'name' => $category->name,
        ]));

        $this->assertNotHaveErrors('name', new CategoryRequest([
            'category_id' => $category->id,
            'name' => $category->name,
        ]));
    }

    public function testTypeIsRequiredAndIntegerAndInValidValues(): void
    {
        $this->assertHasErrors('type', new CategoryRequest([
            'type' => null,
        ]));

        $this->assertHasErrors('type', new CategoryRequest([
            'type' => 'string!',
        ]));

        $this->assertHasErrors('type', new CategoryRequest([
            'type' => 4,
        ]));

        $this->assertNotHaveErrors('type', new CategoryRequest([
            'type' => 1,
        ]));
    }
}
