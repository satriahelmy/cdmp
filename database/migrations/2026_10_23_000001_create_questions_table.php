<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 100)->unique();
            $table->unsignedInteger('original_number')->nullable();
            $table->longText('text');
            $table->longText('explanation')->nullable();
            $table->unsignedInteger('source_page')->nullable();
            $table->string('source_type')->nullable();
            $table->string('review_status', 50);
            $table->boolean('needs_key_review')->default(false);
            $table->boolean('key_verified_against_dmbok')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
