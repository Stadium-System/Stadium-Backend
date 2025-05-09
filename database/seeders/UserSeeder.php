<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'phone_number' => '218912345678',
            'password' => bcrypt('aaaa5555'),
            'type' => 'admin',
            'status' => 'active',
        ])->assignRole('admin');

        User::create([
            'name' => 'John Doe',
            'phone_number' => '218912345679',
            'password' => bcrypt('aaaa5555'),
            'type' => 'user',
            'status' => 'active',
        ])->assignRole('user');

        User::create([
            'name' => 'Jane Smith',
            'phone_number' => '218912345680',
            'password' => bcrypt('aaaa5555'),
            'type' => 'user',
            'status' => 'active',
        ])->assignRole('user');
        
        // Create an owner user
        User::create([
            'name' => 'Stadium Owner',
            'phone_number' => '218912345681',
            'password' => bcrypt('aaaa5555'),
            'type' => 'owner',
            'status' => 'active',
        ])->assignRole('owner');
    }
}