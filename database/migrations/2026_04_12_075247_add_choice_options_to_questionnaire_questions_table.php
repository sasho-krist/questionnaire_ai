<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->json('choice_options')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->dropColumn('choice_options');
        });
    }
};
