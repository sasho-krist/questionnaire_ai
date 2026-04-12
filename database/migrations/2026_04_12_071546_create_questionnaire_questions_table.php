<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('questionnaire_sections')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_questions');
    }
};
