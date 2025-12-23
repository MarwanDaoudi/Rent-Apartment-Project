<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '99999999'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '99999999',
                'birthday' => '1990-01-01',
                'balance' => '1000000',
                'profile_image' => 'default.png',
                'id_image' => 'default-id.png',
                'role' => 'admin',
                'password' => Hash::make('12345678'),
            ]
        );
    }
}
