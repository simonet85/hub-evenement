<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->char('reference', 26)->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->unsignedInteger('amount');
            $table->unsignedTinyInteger('quantity')->nullable();
            $table->string('contributor_name', 160);
            $table->string('phone', 30);
            $table->string('message', 300)->nullable();
            $table->boolean('anonymous')->default(false);
            $table->string('status', 20)->default('pending');
            $table->string('provider', 20)->default('moneyfusion');
            $table->string('provider_token', 190)->nullable()->unique();
            $table->string('provider_url', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_note', 255)->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->string('reconciliation_note', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index('guest_id');
            $table->index('gift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
