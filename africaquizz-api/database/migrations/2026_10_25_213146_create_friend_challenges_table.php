<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('friend_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('creator_guest_id')->nullable()->constrained('guest_sessions')->cascadeOnDelete();
            $table->string('code')->unique(); // code court partageable
            $table->string('creator_pseudo');
            $table->jsonb('question_ids'); // 5 questions
            $table->integer('creator_score')->default(0);
            $table->integer('creator_correct')->default(0);
            $table->foreignUuid('opponent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('opponent_guest_id')->nullable()->constrained('guest_sessions')->nullOnDelete();
            $table->string('opponent_pseudo')->nullable();
            $table->integer('opponent_score')->default(0);
            $table->integer('opponent_correct')->default(0);
            $table->timestamp('opponent_played_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('code');
        });
    }
    public function down(): void { Schema::dropIfExists('friend_challenges'); }
};