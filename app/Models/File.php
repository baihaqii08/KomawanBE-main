<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class File extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'folder_id',
        'user_id',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'storage_path',
        'public_url',
        'visibility',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
