<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiHistory extends Model
{
    protected $fillable = [
        'method',
        'url',
        'request_headers',
        'request_body',
        'status_code',
        'response_headers',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'request_headers' => 'array',
    ];
}
