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
            $table->string('invitation_token', 64)->nullable();
            $table->string('rsvp_status', 20)->default('pending');
            $table->unsignedTinyInteger('rsvp_seats')->nullable();
            $table->text('attendee_names')->nullable();
            $table->text('dietary_notes')->nullable();
            $table->text('rsvp_message')->nullable();
            $table->timestamp('rsvp_responded_at')->nullable();
            $table->boolean('rsvp_is_late')->default(false);
        });

        DB::table('guests')->chunkById(100, function ($guests) {
            foreach ($guests as $guest) {
                DB::table('guests')
                    ->where('id', $guest->id)
                    ->update(['invitation_token' => Str::random(48)]);
            }
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->unique('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique('guests_invitation_token_unique');
            $table->dropColumn([
                'invitation_token',
                'rsvp_status',
                'rsvp_seats',
                'attendee_names',
                'dietary_notes',
                'rsvp_message',
                'rsvp_responded_at',
                'rsvp_is_late',
            ]);
        });
    }
};
