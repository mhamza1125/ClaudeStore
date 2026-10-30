<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // B2B price access (Admin → B2B settings). pending | approved | rejected
            $table->string('approval_status', 20)->nullable()->index()->after('is_active');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            // Individual override: default (follow the B2B rules) | allow (always see prices) | deny (never)
            $table->string('price_access', 10)->default('default')->after('approved_by');
        });

        // Accounts that existed before approval was introduced keep their access.
        DB::table('users')->update(['approval_status' => 'approved', 'approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'approved_at', 'price_access']);
        });
    }
};
