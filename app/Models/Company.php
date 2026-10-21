<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'website', 'country', 'vat_number', 'business_type', 'customer_group_id',
        'wholesale_status', 'wholesale_application', 'wholesale_requested_at', 'wholesale_reviewed_at',
        'wholesale_reviewed_by', 'sales_rep_id', 'payment_terms', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_requested_at' => 'datetime',
            'wholesale_reviewed_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /** Only approved wholesale accounts receive group and negotiated pricing. */
    public function isWholesaleApproved(): bool
    {
        return $this->wholesale_status === 'approved';
    }
}
