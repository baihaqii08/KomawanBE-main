<?php

namespace Database\Seeders;

use App\Models\File;

use Illuminate\Database\Seeder;

class FileSeeder extends Seeder
{
    public function run(): void
    {
        $users = \App\Models\User::all();

        foreach ($users as $user) {
            File::firstOrCreate([
                'folder_id' => null,
                'user_id' => $user->id,
                'filename' => "sample_{$user->id}.pdf",
                'original_name' => "sample.pdf",
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'size' => 1024 * 500, // 500 KB
                'storage_path' => "{$user->id}/sample_{$user->id}.pdf",
                'public_url' => null,
                'visibility' => 'private'
            ]);
        }
    }
}
