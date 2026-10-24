<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->unsignedSmallInteger('total_questions');
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->unsignedSmallInteger('incorrect_count')->nullable();
            $table->unsignedSmallInteger('unanswered_count')->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
