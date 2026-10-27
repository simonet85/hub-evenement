<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_program_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('start_time', 5);
            $table->string('end_time', 5)->nullable();
            $table->string('title', 120);
            $table->string('location', 120)->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_program_steps');
    }
};
