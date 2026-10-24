<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 120)->nullable();
            $table->string('title', 160);
            $table->string('subtitle', 400)->nullable();
            // Own banner image (public disk) or, when empty, the linked product's photograph.
            $table->string('image_path')->nullable();
            $table->string('image_alt', 200)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_focus', 10)->default('center'); // CSS object-position keyword
            $table->string('primary_label', 60)->nullable();
            $table->string('primary_url')->nullable();
            $table->string('secondary_label', 60)->nullable();
            $table->string('secondary_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
