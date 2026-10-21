<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable(); // JSON-encoded
            $table->timestamps();
        });

        Schema::create('reference_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();
            $table->unique(['name', 'year']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80)->index();
            $table->nullableMorphs('subject');
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reference_sequences');
        Schema::dropIfExists('settings');
    }
};
