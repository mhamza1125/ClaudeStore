<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAttachment extends Model
{
    protected $fillable = ['quote_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
