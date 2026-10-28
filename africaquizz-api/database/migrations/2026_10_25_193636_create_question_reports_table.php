<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('guest_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reason'); // wrong_answer, ambiguous, outdated, offensive, typo, image
            $table->text('comment')->nullable();
            $table->string('status')->default('pending'); // pending, resolved, dismissed
            $table->foreignUuid('reviewed_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->boolean('xp_compensation_granted')->default(false);
            $table->integer('xp_compensation_amount')->default(0);
            $table->timestamps();
            // 1 signalement par (user OU guest) par question
            $table->unique(['question_id', 'user_id'], 'unique_report_user');
            $table->unique(['question_id', 'guest_session_id'], 'unique_report_guest');
            $table->index(['question_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('question_reports'); }
};