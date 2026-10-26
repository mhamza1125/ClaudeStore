<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The view (front, back, ...) is optional: an image may not depict a specific angle. */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('view_type', 20)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('product_images')->whereNull('view_type')->update(['view_type' => 'front']);
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('view_type', 20)->default('front')->change();
        });
    }
};
