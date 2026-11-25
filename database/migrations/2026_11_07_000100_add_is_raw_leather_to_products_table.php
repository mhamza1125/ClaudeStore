<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw leather (hides / sides / skins) sold by quotation only; shown in its own homepage gallery.
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_raw_leather')->default(false)->after('is_bestseller')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_raw_leather']);
            $table->dropColumn('is_raw_leather');
        });
    }
};
