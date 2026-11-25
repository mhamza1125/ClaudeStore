<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive (audit F-11): remembers a product's previous slugs so old URLs answer with a 301
 * to the current one. New table only; no existing row is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_slug_redirects');
    }
};
