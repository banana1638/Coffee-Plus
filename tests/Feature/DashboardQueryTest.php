<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\DashboardMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_dashboard_displays_products_with_preloaded_rating(): void
    {
        $menu = $this->createMenu('Drink');
        $product = $this->createProduct($menu, 'Caffe Latte');
        $this->createReview($product, 4);

        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Caffe Latte')
            ->assertSee('4.0/5');
    }

    public function test_api_dashboard_returns_only_active_products(): void
    {
        $menu = $this->createMenu('Drink');
        $active = $this->createProduct($menu, 'Active Latte', true);
        $inactive = $this->createProduct($menu, 'Hidden Latte', false);
        $this->createReview($active, 5);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('menus.0.category_name', 'Drink')
            ->assertJsonPath('menus.0.product_count', 1)
            ->assertJsonPath('menus.0.products.0.name', 'Active Latte')
            ->assertJsonPath('menus.0.products.0.average_rating', 5);

        $this->assertStringNotContainsString('Hidden Latte', $response->getContent());
        $this->assertNotSame($inactive->id, $response->json('menus.0.products.0.id'));
    }

    public function test_api_dashboard_search_filters_products(): void
    {
        $menu = $this->createMenu('Drink');
        $this->createProduct($menu, 'Matcha Latte');
        $this->createProduct($menu, 'Americano');

        $response = $this->getJson('/api/dashboard?search=Matcha');

        $response->assertStatus(200)
            ->assertJsonPath('menus.0.product_count', 1)
            ->assertJsonPath('menus.0.products.0.name', 'Matcha Latte');
    }

    public function test_dashboard_menu_query_matches_product_table_columns(): void
    {
        $menu = $this->createMenu('Drink');
        $this->createProduct($menu, 'Flat White');

        $menus = app(DashboardMenuService::class)->menus(null, 'all', false, false);

        $this->assertArrayNotHasKey('description', $menus->first()->products->first()->getAttributes());
    }

    private function createMenu(string $name): Menu
    {
        $menu = new Menu();
        $menu->name = $name;
        $menu->save();

        return $menu;
    }

    private function createProduct(Menu $menu, string $name, bool $active = true): Product
    {
        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = $name;
        $product->price = 10.00;
        $product->oz_redeem_value = 100;
        $product->is_active = $active;
        $product->save();

        return $product;
    }

    private function createReview(Product $product, int $rating): void
    {
        $user = User::factory()->create();
        $order = new Order();
        $order->user_id = $user->id;
        $order->bill_id = 'CP-REVIEW-' . $product->id . '-' . $rating;
        $order->subtotal = 10.00;
        $order->final_amount = 10.00;
        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => $rating,
        ]);
    }
}
