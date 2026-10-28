<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('user_daily_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('guest_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_session_id')->constrained()->cascadeOnDelete();
            $table->date('challenge_date');
            $table->integer('score');
            $table->integer('correct_answers');
            $table->timestamps();
            $table->unique(['user_id', 'challenge_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_daily_results'); }
};