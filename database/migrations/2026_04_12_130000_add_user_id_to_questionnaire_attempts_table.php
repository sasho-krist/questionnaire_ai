<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_attempts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('questionnaire_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_attempts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
