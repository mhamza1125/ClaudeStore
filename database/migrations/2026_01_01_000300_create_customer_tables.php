<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Optional volume discount in basis points (100 = 1%) applied to
            // standard tier/base prices when no group tier price exists.
            $table->unsignedInteger('discount_bps')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('vat_number', 60)->nullable();
            $table->string('business_type', 60)->nullable();
            $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();
            // none | pending | approved | rejected
            $table->string('wholesale_status', 20)->default('none')->index();
            $table->text('wholesale_application')->nullable();
            $table->timestamp('wholesale_requested_at')->nullable();
            $table->timestamp('wholesale_reviewed_at')->nullable();
            $table->foreignId('wholesale_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_terms')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // billing | shipping
            $table->string('label')->nullable();
            $table->string('recipient')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country', 2);
            $table->string('phone', 40)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });
        Schema::dropIfExists('companies');
        Schema::dropIfExists('customer_groups');
    }
};
