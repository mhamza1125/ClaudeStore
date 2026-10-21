<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->string('product_code', 64)->nullable()->index();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            // Material facts are only shown when supplied; never inferred.
            $table->string('material')->nullable()->index();
            $table->string('leather_type')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('hardware')->nullable();
            $table->string('finishing')->nullable();
            $table->string('packaging')->nullable();
            $table->string('lead_time')->nullable();
            $table->json('specifications')->nullable(); // [{label, value}]
            $table->unsignedBigInteger('base_price_cents')->nullable();
            $table->unsignedInteger('moq')->default(1);
            $table->unsignedInteger('order_multiple')->default(1);
            $table->string('status', 20)->default('draft')->index(); // draft | published | archived
            $table->boolean('quote_enabled')->default(true);
            $table->boolean('public_pricing')->default(true);
            $table->boolean('price_on_request')->default(false);
            $table->boolean('is_custom')->default(false); // custom / made-to-order
            $table->boolean('customizable')->default(false); // shows "Request customization"
            $table->string('availability', 20)->default('in_stock')->index();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->string('spec_sheet_path')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->string('color')->nullable()->index();
            $table->string('size')->nullable();
            $table->string('finish')->nullable();
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->string('availability', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->string('disk', 20)->default('public');
            $table->string('path')->nullable();
            // Untouched original on the private disk; never overwritten.
            $table->string('original_disk', 20)->nullable();
            $table->string('original_path')->nullable();
            $table->string('view_type', 20)->default('front');
            $table->string('alt')->nullable();
            // processing | pending_review | approved | rejected | failed
            $table->string('status', 20)->default('approved')->index();
            $table->string('source', 20)->default('upload'); // upload | processed | generated | demo
            $table->string('provider', 40)->nullable();
            $table->text('provider_notes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();
            $table->index(['product_id', 'variant_id', 'customer_group_id', 'min_qty'], 'price_tiers_lookup');
        });

        Schema::create('customer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedBigInteger('unit_price_cents');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'product_id', 'variant_id'], 'customer_prices_lookup');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('customer_prices');
        Schema::dropIfExists('price_tiers');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
