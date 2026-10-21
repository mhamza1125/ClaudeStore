<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteActivity extends Model
{
    protected $fillable = ['quote_id', 'user_id', 'type', 'body', 'meta', 'is_internal'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_internal' => 'boolean'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
