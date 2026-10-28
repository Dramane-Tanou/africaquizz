<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('answer_given')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('client_elapsed_ms')->nullable();
            $table->integer('rtt_ms')->nullable();
            $table->integer('speed_bonus')->default(0);
            $table->integer('score_gained')->default(0);
            $table->integer('xp_gained')->default(0);
            $table->integer('combo_streak')->default(0);
            $table->timestamps();
            $table->unique(['quiz_session_id', 'question_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_answers'); }
};