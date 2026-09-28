<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_off_request_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function request()
    {
        return $this->belongsTo(TimeOffRequest::class, 'time_off_request_id');
    }
}
