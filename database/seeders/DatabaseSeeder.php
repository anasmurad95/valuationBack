<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'nameAr' => 'مدير',
            'nameEn' => 'Admin',
            'email' => 'admin@admin.com',
            'phone' => '0799999999',
            'gender' => 'male',
            'role' => 'admin',
            'position' => 'System Administrator',
            'is_active' => true,
            'language' => 'ar',
            'password' => Hash::make('123456789'), // Change to a secure password
        ]);
        Client::create([
            'nameAr' => 'مثال',
            'nameEn' => 'Example',
            'email' => 'ex@example.com',
            'phone' => '0781...',
            'gender' => 1,
            'password' => bcrypt('your_password'),
        ]);
    }
}