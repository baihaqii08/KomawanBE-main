<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 Super Admin
        if (User::role('SUPER_ADMIN')->count() === 0) {
            $superAdmin = User::firstOrCreate(
                ['email' => 'superadmin@example.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                ]
            );
            $superAdmin->assignRole('SUPER_ADMIN');
        }

        // 3 Admins
        for ($i = 1; $i <= 3; $i++) {
            $admin = User::firstOrCreate(
                ['email' => "admin{$i}@example.com"],
                [
                    'name' => "Admin {$i}",
                    'password' => Hash::make('password'),
                ]
            );
            $admin->assignRole('ADMIN');
        }

        // 20 Users
        for ($i = 1; $i <= 20; $i++) {
            $user = User::firstOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'name' => "User {$i}",
                    'password' => Hash::make('password'),
                ]
            );
            $user->assignRole('USER');
        }
    }
}
