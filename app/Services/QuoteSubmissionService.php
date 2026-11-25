<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\QuoteRequestReceived;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns the buyer's quote list into a persisted quote request. Line items, prices
 * and product details are taken from the server-side basket and catalogue only.
 */
class QuoteSubmissionService
{
    public function __construct(private QuoteBasketService $basket, private ReferenceGenerator $references) {}

    /**
     * @param  array<string,mixed>  $data  validated contact/request fields
     * @param  array<int,UploadedFile>  $files
     *
     * @throws ValidationException
     */
    public function submit(array $data, array $files, ?User $user, ?string $ip): Quote
    {
        $lines = $this->basket->lines();
        $this->assertSubmittable($lines, $data);

        $storedPaths = [];
        try {
            $quote = DB::transaction(function () use ($data, $files, $user, $ip, $lines, &$storedPaths) {
                $quote = Quote::create([
                    ...collect($data)->only([
                        'company_name', 'contact_name', 'phone', 'country', 'delivery_destination', 'website',
                        'business_type', 'expected_volume', 'target_delivery_date', 'specifications', 'message',
                    ])->all(),
                    'email' => strtolower($data['email']),
                    'preferred_currency' => $data['preferred_currency'] ?? 'USD',
                    'reference' => $this->references->next('Q'),
                    'status' => QuoteStatus::Pending,
                    'user_id' => $user?->id,
                    'company_id' => $user?->company_id,
                    'assigned_to' => $user?->company?->sales_rep_id,
                    'currency' => config('store.currency'),
                    'submitted_at' => now(),
                    'submitted_ip' => $ip,
                ]);

                foreach ($lines->values() as $i => ['item' => $item, 'price' => $price]) {
                    $product = $item->product;
                    $quote->items()->create([
                        'product_id' => $product->id,
                        'variant_id' => $item->variant_id,
                        'sku' => $item->variant?->sku ?? $product->sku,
                        'product_name' => $product->name,
                        'variant_name' => $item->variant?->name,
                        'product_snapshot' => [
                            'material' => $product->material, 'dimensions' => $product->dimensions,
                            'color' => $item->variant?->color, 'size' => $item->variant?->size,
                            'lead_time' => $product->lead_time, 'moq' => $price->moq,
                            'image' => $product->primaryImage()?->url('thumb'),
                        ],
                        'quantity' => $item->quantity,
                        'customization' => $item->customization,
                        'notes' => $item->notes,
                        // Only prices the buyer was allowed to see are recorded as "indicative".
                        'indicative_unit_price_cents' => $price->displayUnitCents(),
                        'indicative_price_source' => $price->visible ? $price->source->value : null,
                        'sort_order' => $i,
                    ]);
                }

                foreach ($files as $file) {
                    $path = $file->storeAs("quote-attachments/{$quote->id}", Str::uuid().'.'.$file->extension(), 'local');
                    $storedPaths[] = $path;
                    $quote->attachments()->create([
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                QuoteActivity::create([
                    'quote_id' => $quote->id, 'user_id' => $user?->id, 'type' => 'submitted', 'is_internal' => false,
                    'body' => 'Quote request submitted with '.$lines->count().' line(s).',
                ]);

                return $quote;
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $p) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($p);
            }
            throw $e;
        }

        $this->basket->clear();
        $this->notify($quote);

        return $quote;
    }

    private function assertSubmittable(Collection $lines, array $data): void
    {
        if ($lines->isEmpty() && blank($data['specifications'] ?? null) && blank($data['message'] ?? null)) {
            throw ValidationException::withMessages(['items' => 'Add products to your quote list, or describe what you need in the specifications or message field.']);
        }

        foreach ($lines as ['item' => $item, 'price' => $price]) {
            if (! $item->product->canBeQuoted()) {
                throw ValidationException::withMessages(['items' => "“{$item->product->name}” is no longer available for quotation. Please remove it from your list."]);
            }
            if ($item->variant && ! $item->variant->is_active) {
                throw ValidationException::withMessages(['items' => "“{$item->product->name}” ({$item->variant->name}) is no longer available. Please remove it from your list."]);
            }
            if (! $price->isValidQuantity()) {
                throw ValidationException::withMessages(['items' => "{$item->product->name}: {$price->errors[0]}"]);
            }
        }

        $min = (int) setting('order.min_value_cents', 0);
        $total = $this->basket->indicativeTotal($lines);
        if ($min > 0 && $total !== null && $total < $min) {
            throw ValidationException::withMessages(['items' => 'The minimum order value is '.money($min).'.']);
        }
    }

    private function notify(Quote $quote): void
    {
        try {
            Mail::to($quote->email, $quote->contact_name)->queue(new QuoteRequestReceived($quote));
            $staff = collect([setting('sales.notification_email'), $quote->assignee?->email])->filter()->unique();
            if ($staff->isNotEmpty()) {
                Mail::to($staff->all())->queue(new NewQuoteRequestAlert($quote));
            }
        } catch (\Throwable $e) {
            // The quote is saved; a mail transport/queue outage must not lose it.
            Log::error('Quote notification dispatch failed', ['quote' => $quote->reference, 'error' => $e->getMessage()]);
            QuoteActivity::create(['quote_id' => $quote->id, 'type' => 'email_failed', 'body' => 'Notification emails could not be queued: '.$e->getMessage()]);
        }
    }
}
