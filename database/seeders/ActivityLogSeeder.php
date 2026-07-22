<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(5)->get();

        foreach ($users as $user) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'Login',
                'details' => 'User logged in successfully.'
            ]);
        }
    }
}
