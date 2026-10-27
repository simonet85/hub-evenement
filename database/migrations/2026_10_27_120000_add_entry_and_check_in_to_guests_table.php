<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('entry_token', 64)->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('guests')->chunkById(100, function ($guests) {
            foreach ($guests as $guest) {
                DB::table('guests')
                    ->where('id', $guest->id)
                    ->update(['entry_token' => Str::random(48)]);
            }
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->unique('entry_token');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropForeign(['checked_in_by']);
            $table->dropUnique('guests_entry_token_unique');
            $table->dropColumn([
                'entry_token',
                'checked_in_at',
                'checked_in_by',
            ]);
        });
    }
};
