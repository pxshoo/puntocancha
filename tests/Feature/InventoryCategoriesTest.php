<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_replace(['name' => 'Agua', 'category_choice' => 'default:Bebidas', 'cost_price' => 500,
            'sale_price' => 1000, 'initial_stock' => 5, 'alert_min_stock' => 2, 'is_active' => 1], $overrides);
    }

    public function test_default_options_are_available_without_mutating_on_get_and_save_category_with_product(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('inventory.create'))->assertOk()->assertSee('Bebidas')->assertSee('Alimentos')->assertSee('Dulces')->assertSee('Guardar cambios');
        $this->assertSame(0, ProductCategory::count());
        $this->post(route('inventory.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect(route('inventory.index'));
        $this->assertSame('Bebidas', Product::first()->category->name);
        $this->assertSame(5, Product::first()->stock);
        $this->post(route('inventory.store'), $this->data(['name' => 'Jugo']))->assertSessionHasNoErrors();
        $this->assertSame(1, ProductCategory::count());
        $this->get(route('inventory.create'))->assertOk()->assertSee('existing:'.ProductCategory::first()->id);
        $this->get(route('inventory.index'))->assertOk()->assertDontSee('Crear categoría')->assertSee('Guardar cambios');
    }

    public function test_new_category_is_saved_with_product_and_validation_failure_does_not_create_it(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('inventory.store'), $this->data(['category_choice' => 'new', 'new_category' => 'Suplementos', 'sale_price' => 0]))
            ->assertSessionHasErrors('sale_price');
        $this->assertSame(0, ProductCategory::count());
        $this->post(route('inventory.store'), $this->data(['category_choice' => 'new', 'new_category' => 'Suplementos']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Suplementos', Product::first()->category->name);
        $product = Product::first();
        $data = $this->data(['name' => 'Agua editada', 'category_choice' => 'default:Alimentos']);
        unset($data['initial_stock']);
        $this->put(route('inventory.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertSame('Alimentos', $product->fresh()->category->name);
        $this->assertSame(5, $product->fresh()->stock);
        $this->post(route('inventory.store'), $this->data(['category_choice' => 'new', 'new_category' => '']))
            ->assertSessionHasErrors('new_category');
    }

    public function test_other_club_category_is_rejected_and_default_categories_are_isolated(): void
    {
        $this->actingAs(User::factory()->create());
        $category = ProductCategory::create(['name' => 'Bebidas']);
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->post(route('inventory.store'), $this->data(['category_choice' => 'existing:'.$category->id]))
            ->assertSessionHasErrors('category_id');
        $this->post(route('inventory.store'), $this->data())->assertSessionHasNoErrors();
        $this->assertNotSame($category->id, Product::first()->category_id);
        $this->post(route('inventory.store'), $this->data(['category_choice' => 'default:No permitida']))
            ->assertSessionHasErrors('default_category');
    }
}
