<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('cagnotte_enabled')->default(false)->after('dress_code');
            $table->unsignedInteger('cagnotte_goal')->nullable()->after('cagnotte_enabled');
            $table->string('cagnotte_message', 500)->nullable()->after('cagnotte_goal');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['cagnotte_enabled', 'cagnotte_goal', 'cagnotte_message']);
        });
    }
};
