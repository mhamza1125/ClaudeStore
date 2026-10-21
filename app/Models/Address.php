<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'company_id', 'type', 'label', 'recipient', 'line1', 'line2', 'city', 'region', 'postal_code', 'country', 'phone', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function singleLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->region, $this->postal_code, $this->country])
            ->filter()->implode(', ');
    }
}
