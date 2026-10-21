<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'reference', 'quote_id', 'company_id', 'user_id', 'created_by', 'status', 'company_name', 'contact_name',
        'email', 'phone', 'country', 'delivery_destination', 'po_reference', 'currency', 'subtotal_cents',
        'discount_cents', 'shipping_cents', 'additional_charges_cents', 'additional_charges_label',
        'tax_rate_bps', 'tax_cents', 'total_cents', 'payment_terms', 'delivery_terms', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'additional_charges_cents' => 'integer',
            'tax_rate_bps' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
