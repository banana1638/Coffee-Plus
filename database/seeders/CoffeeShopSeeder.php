<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoffeeShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $coffee = \App\Models\Menu::create(['name' => 'Signature Coffee']);
        $food = \App\Models\Menu::create(['name' => 'All-Day Food']);

        $coffee->products()->createMany([
            ['name' => 'Flat White', 'price' => 12.00, 'oz_redeem_value' => 1200],
            ['name' => 'Cold Brew', 'price' => 15.00, 'oz_redeem_value' => 1500],
            [ 'name' => 'Spanish Latte', 'price' => 15.0, 'oz_redeem_value' => 750],
            ['name' => 'Dirty Coffee', 'price' => 13.0, 'oz_redeem_value' => 650],
            ['name' => 'Cold Brew Black', 'price' => 12.0, 'oz_redeem_value' => 600],
            ['name' => 'Oatmilk Latte', 'price' => 16.0, 'oz_redeem_value' => 800],
            ['name' => 'Uji Matcha Latte', 'price' => 14.0, 'oz_redeem_value' => 700],
            ['name' => 'Belgian Cocoa', 'price' => 13.0, 'oz_redeem_value' => 650],
            ['name' => 'Peach Oolong', 'price' => 10.0, 'oz_redeem_value' => 500],
            ['name' => 'Rose Lychee Tea', 'price' => 11.0, 'oz_redeem_value' => 550],
            ['name' => 'Butter Croissant', 'price' => 8.0, 'oz_redeem_value' => 400],
            ['name' => 'Classic Canelé', 'price' => 9.0, 'oz_redeem_value' => 450],
        ]);

        $food->products()->createMany([
            ['name' => 'Sourdough Toast', 'price' => 18.00, 'oz_redeem_value' => 1800],
            ['name' => 'Croissant', 'price' => 9.00, 'oz_redeem_value' => 900],
        ]);

        $user = \App\Models\User::first();
        if ($user) {
            $user->update([
                'tangki_balance' => 50.00,
                'tangki_oz' => 2000,
            ]);
        }
    }
}
