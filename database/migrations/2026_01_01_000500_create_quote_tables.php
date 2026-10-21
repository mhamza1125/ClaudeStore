<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_baskets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('quote_basket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_basket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('customization')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            // Buyer-submitted contact details (snapshot at submission time)
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email')->index();
            $table->string('phone', 40);
            $table->char('country', 2)->index();
            $table->string('delivery_destination');
            $table->string('website')->nullable();
            $table->string('business_type', 60)->nullable();
            $table->string('expected_volume', 120)->nullable();
            $table->date('target_delivery_date')->nullable();
            $table->char('preferred_currency', 3)->default('USD');
            $table->text('specifications')->nullable();
            $table->text('message')->nullable();
            $table->string('po_reference', 80)->nullable();
            // Commercial terms (set by staff). All money in integer cents.
            $table->char('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->default(0);
            $table->unsignedBigInteger('additional_charges_cents')->default(0);
            $table->string('additional_charges_label')->nullable();
            $table->unsignedInteger('tax_rate_bps')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->date('valid_until')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('customer_notes')->nullable(); // shown on the quotation
            $table->unsignedInteger('version')->default(0); // last sent version
            $table->boolean('has_unsent_changes')->default(false);
            $table->string('access_token_hash', 64)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('accepted_by_name')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('submitted_ip', 45)->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // Snapshot: never re-read from the catalogue after submission.
            $table->string('sku', 64);
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->json('product_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->text('customization')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('indicative_unit_price_cents')->nullable();
            $table->string('indicative_price_source', 40)->nullable();
            $table->unsignedBigInteger('unit_price_cents')->nullable(); // negotiated/final
            $table->unsignedInteger('discount_bps')->default(0);
            $table->unsignedBigInteger('line_total_cents')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quote_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['quote_id', 'version']);
        });

        Schema::create('quote_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('quote_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // note | status | price_change | sent | ...
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('quote_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('confirmed')->index();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('delivery_destination')->nullable();
            $table->string('po_reference', 80)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('discount_cents');
            $table->unsignedBigInteger('shipping_cents');
            $table->unsignedBigInteger('additional_charges_cents');
            $table->string('additional_charges_label')->nullable();
            $table->unsignedInteger('tax_rate_bps');
            $table->unsignedBigInteger('tax_cents');
            $table->unsignedBigInteger('total_cents');
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('sku', 64);
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('discount_bps')->default(0);
            $table->unsignedBigInteger('line_total_cents');
            $table->text('customization')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sales_order_items', 'sales_orders', 'quote_activities', 'quote_attachments', 'quote_versions', 'quote_items', 'quotes', 'quote_basket_items', 'quote_baskets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
