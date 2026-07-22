<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'action',
        'details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
