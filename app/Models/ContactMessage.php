<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A Contact Us form submission. */
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'company', 'subject', 'message', 'user_id', 'ip_address', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
