<?php

namespace App\Modules\Files\Services;

use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\ActivityLog;

class FileService
{
    public function getUserFiles(string $folderId = null)
    {
        $query = File::where('user_id', Auth::id());
        
        if ($folderId === 'root' || is_null($folderId)) {
            $query->whereNull('folder_id');
        } else {
            $query->where('folder_id', $folderId);
        }

        return $query->get();
    }

    public function uploadFile(array $data, $uploadedFile)
    {
        $userId = Auth::id();
        $folderId = ($data['folder_id'] === 'root') ? null : $data['folder_id'];
        
        // Supabase storage path: {user_id}/[folder_id]/time_filename
        $pathPrefix = $userId . ($folderId ? "/{$folderId}" : "");
        
        $filename = time() . '_' . $uploadedFile->getClientOriginalName();
        
        $disk = env('SUPABASE_URL') ? 'supabase' : 'public';
        $path = $uploadedFile->storeAs($pathPrefix, $filename, $disk);
        
        if (!$path) {
            throw ValidationException::withMessages([
                'file' => ['Failed to upload file to storage.']
            ]);
        }
        
        // Construct public URL
        if ($disk === 'supabase') {
            $publicUrl = env('SUPABASE_URL') . '/storage/v1/object/public/files/' . $path;
        } else {
            $publicUrl = asset('storage/' . $path);
        }

        $file = File::create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'filename' => $filename,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'size' => $uploadedFile->getSize(),
            'storage_path' => $path,
            'public_url' => $publicUrl,
            'visibility' => 'private',
        ]);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'Upload',
            'details' => "Uploaded file: {$file->original_name}",
        ]);

        return $file;
    }

    public function renameFile(string $id, string $newName)
    {
        $file = File::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $oldName = $file->original_name;
        $file->update(['original_name' => $newName]);
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Rename',
            'details' => "Renamed file from {$oldName} to {$newName}",
        ]);
        
        return $file;
    }

    public function deleteFile(string $id)
    {
        $file = File::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        // Since we are using soft deletes, we don't delete from supabase right away.
        // TODO: Create a scheduled command to hard delete and clean up storage
        $file->delete();
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Delete',
            'details' => "Soft deleted file: {$file->original_name}",
        ]);
        
        return true;
    }

    public function sync(array $data)
    {
        $userId = Auth::id();
        
        if (!str_starts_with($data['storage_path'], $userId . '/')) {
            throw ValidationException::withMessages([
                'storage_path' => ['Invalid storage path ownership.']
            ]);
        }
        
        $folderId = (isset($data['folder_id']) && $data['folder_id'] !== 'root') ? $data['folder_id'] : null;
        
        $file = File::create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'filename' => $data['filename'],
            'original_name' => $data['original_name'],
            'mime_type' => $data['mime_type'] ?? 'application/octet-stream',
            'extension' => $data['extension'] ?? '',
            'size' => $data['size'],
            'storage_path' => $data['storage_path'],
            'public_url' => env('SUPABASE_URL') . '/storage/v1/object/public/files/' . $data['storage_path'],
            'visibility' => 'private',
        ]);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'Upload (Sync)',
            'details' => "Synced uploaded file: {$file->original_name}",
        ]);

        return $file;
    }

    public function syncDelete(array $paths)
    {
        $userId = Auth::id();
        
        foreach ($paths as $path) {
            if (!str_starts_with($path, $userId . '/')) {
                throw ValidationException::withMessages([
                    'paths' => ['Invalid storage path ownership.']
                ]);
            }
        }
        
        $files = File::where('user_id', $userId)->whereIn('storage_path', $paths)->get();
        foreach ($files as $file) {
            $file->delete();
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'Delete (Sync)',
                'details' => "Synced soft deleted file: {$file->original_name}",
            ]);
        }
        return true;
    }
}
