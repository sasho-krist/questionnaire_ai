<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('user_title');
            $table->text('topic_keywords');
            $table->json('title_suggestions')->nullable();
            $table->string('chosen_title')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
