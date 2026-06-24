<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Menu;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_stock_tracking(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Stock Latte',
                'price' => 12.50,
                'menu_id' => $menu->id,
                'oz_redeem_value' => 150,
                'track_stock' => 1,
                'stock' => 25,
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Stock Latte',
            'track_stock' => true,
            'stock' => 25,
        ]);
    }

    public function test_admin_can_update_product_stock_tracking(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();
        $product = $this->createProduct($menu, [
            'track_stock' => true,
            'stock' => 8,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'name' => 'Open Stock Latte',
                'price' => 13.00,
                'menu_id' => $menu->id,
                'oz_redeem_value' => 130,
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Open Stock Latte',
            'track_stock' => false,
            'stock' => null,
        ]);
    }

    public function test_admin_can_update_product_image(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();
        $product = $this->createProduct($menu);
        $image = UploadedFile::fake()->image('updated-latte.jpg', 600, 600);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'name' => 'Latte With Photo',
                'price' => 13.00,
                'menu_id' => $menu->id,
                'oz_redeem_value' => 130,
                'image' => $image,
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertNotNull($product->image);
        $this->assertNotNull($product->image_thumb);
        $this->assertNotNull($product->image_detail);
        $this->assertFileExists(public_path('images/products/' . $product->image));
        $this->assertFileExists(public_path($product->image_thumb));
        $this->assertFileExists(public_path($product->image_detail));
        $this->assertSame($product->detail_image_url, $product->image_url);

        File::delete(public_path('images/products/' . $product->image));
        File::delete(public_path($product->image_thumb));
        File::delete(public_path($product->image_detail));
    }

    public function test_admin_cannot_upload_svg_product_image(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();
        $image = UploadedFile::fake()->createWithContent(
            'bad.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->actingAs($admin, 'admin')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Unsafe Latte',
                'price' => 12.50,
                'menu_id' => $menu->id,
                'oz_redeem_value' => 150,
                'image' => $image,
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', [
            'name' => 'Unsafe Latte',
        ]);
    }

    public function test_admin_cannot_upload_disguised_product_image(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();
        $image = UploadedFile::fake()->createWithContent(
            'bad.jpg',
            '<html><body>not an image</body></html>'
        );

        $this->actingAs($admin, 'admin')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Fake Image Latte',
                'price' => 12.50,
                'menu_id' => $menu->id,
                'oz_redeem_value' => 150,
                'image' => $image,
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', [
            'name' => 'Fake Image Latte',
        ]);
    }

    public function test_product_index_shows_stock_status(): void
    {
        $admin = $this->createAdmin();
        $menu = $this->createMenu();
        $this->createProduct($menu, [
            'name' => 'Low Stock Latte',
            'track_stock' => true,
            'stock' => 3,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertStatus(200)
            ->assertSee('3 in stock')
            ->assertSee('Low Stock Latte');
    }

    private function createAdmin(): Admin
    {
        $admin = new Admin();
        $admin->name = 'Owner';
        $admin->email = 'product-owner@example.test';
        $admin->password = 'password';
        $admin->role = 'owner';
        $admin->save();

        return $admin;
    }

    private function createMenu(): Menu
    {
        $menu = new Menu();
        $menu->name = 'Coffee';
        $menu->save();

        return $menu;
    }

    private function createProduct(Menu $menu, array $attributes = []): Product
    {
        $product = new Product();
        $product->menu_id = $menu->id;
        $product->name = $attributes['name'] ?? 'Latte';
        $product->price = $attributes['price'] ?? 10.00;
        $product->oz_redeem_value = $attributes['oz_redeem_value'] ?? 100;
        $product->track_stock = $attributes['track_stock'] ?? false;
        $product->stock = $attributes['stock'] ?? null;
        $product->save();

        return $product;
    }
}
