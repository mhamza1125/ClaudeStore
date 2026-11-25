<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive (audit F-12): an edit counter for optimistic locking in the quote workspace.
 * Existing rows get 0; no existing value is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('lock_version')->default(0)->after('has_unsent_changes');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });
    }
};
