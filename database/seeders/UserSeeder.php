<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'test@coffee.com'],
            [
                'name' => 'Coffee Lover',
                'password' => Hash::make('password123'),
                'tangki_balance' => 100.00,
                'tangki_oz' => 2000,
            ]
        );
    }
}