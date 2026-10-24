<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('guest_token_hash', 64)->nullable();
            $table->index(['guest_token_hash', 'status', 'started_at'], 'exam_attempts_guest_status_started_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('exam_attempts_guest_status_started_index');
            $table->dropColumn('guest_token_hash');
        });
    }
};
