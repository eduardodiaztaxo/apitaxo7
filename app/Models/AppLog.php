<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLog extends Model
{
    use HasFactory;

    protected $table = 'app_log';

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'message',
        'metadata',
        'client_at',
        'platform',
        'app_version',
        'device_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'client_at' => 'datetime',
    ];
}