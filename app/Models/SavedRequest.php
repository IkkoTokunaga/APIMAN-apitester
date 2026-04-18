<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedRequest extends Model
{
    protected $fillable = [
        'collection_id',
        'title',
        'method',
        'url',
        'request_headers',
        'request_body',
        'content_type',
        'sort_order',
    ];

    protected $casts = [
        'request_headers' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
