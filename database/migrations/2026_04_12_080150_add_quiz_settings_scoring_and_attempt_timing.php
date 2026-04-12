<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->decimal('points_per_correct', 8, 2)->default(1)->after('status');
            $table->unsignedInteger('seconds_per_question')->nullable()->after('points_per_correct');
        });

        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('correct_option')->nullable()->after('choice_options');
        });

        Schema::table('questionnaire_attempts', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('answers');
            $table->timestamp('deadline_at')->nullable()->after('started_at');
            $table->decimal('score', 10, 2)->nullable()->after('deadline_at');
            $table->decimal('max_score', 10, 2)->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_attempts', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'deadline_at', 'score', 'max_score']);
        });

        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->dropColumn('correct_option');
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn(['points_per_correct', 'seconds_per_question']);
        });
    }
};
