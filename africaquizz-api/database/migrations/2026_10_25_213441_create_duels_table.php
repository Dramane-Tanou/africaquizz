<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('duels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status')->default('pending'); // pending, active, finished, expired
            $table->foreignUuid('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('question_ids');
            $table->integer('player1_score')->default(0);
            $table->integer('player2_score')->default(0);
            $table->integer('player1_correct')->default(0);
            $table->integer('player2_correct')->default(0);
            $table->timestamp('player1_played_at')->nullable();
            $table->timestamp('player2_played_at')->nullable();
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['player1_id', 'player2_id', 'status']);
        });

        Schema::create('duel_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('duel_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->integer('position');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('duel_questions');
        Schema::dropIfExists('duels');
    }
};