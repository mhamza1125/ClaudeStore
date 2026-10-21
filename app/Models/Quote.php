<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'user_id', 'company_id', 'assigned_to', 'status', 'company_name', 'contact_name', 'email',
        'phone', 'country', 'delivery_destination', 'website', 'business_type', 'expected_volume',
        'target_delivery_date', 'preferred_currency', 'specifications', 'message', 'po_reference', 'currency',
        'subtotal_cents', 'discount_cents', 'shipping_cents', 'additional_charges_cents',
        'additional_charges_label', 'tax_rate_bps', 'tax_cents', 'total_cents', 'valid_until', 'payment_terms',
        'delivery_terms', 'customer_notes', 'version', 'has_unsent_changes', 'access_token_hash', 'submitted_at',
        'sent_at', 'responded_at', 'accepted_by_name', 'rejection_reason', 'submitted_ip',
    ];

    protected $hidden = ['access_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'target_delivery_date' => 'date',
            'valid_until' => 'date',
            'submitted_at' => 'datetime',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
            'has_unsent_changes' => 'boolean',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'additional_charges_cents' => 'integer',
            'tax_rate_bps' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuoteVersion::class)->orderByDesc('version');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuoteAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(QuoteActivity::class)->latest('id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->endOfDay()->isPast();
    }

    /** Customer may respond only to a sent, current, unexpired quotation. */
    public function canBeAnsweredByCustomer(): bool
    {
        return $this->status === QuoteStatus::AwaitingResponse && ! $this->has_unsent_changes && ! $this->isExpired();
    }

    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->can('quotes.view-all')) {
            return $q;
        }
        if ($user->hasRole('sales-rep')) {
            return $q->where(fn ($w) => $w->where('assigned_to', $user->id)->orWhereNull('assigned_to'));
        }

        return $q->where(function ($w) use ($user) {
            $w->where('user_id', $user->id);
            if ($user->company_id) {
                $w->orWhere('company_id', $user->company_id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
