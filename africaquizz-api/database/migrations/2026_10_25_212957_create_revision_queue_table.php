<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('revision_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->integer('interval_days')->default(1); // 1, 3, 7, 21
            $table->integer('level')->default(0);          // 0=1j, 1=3j, 2=7j, 3=21j
            $table->integer('consecutive_success')->default(0);
            $table->timestamp('next_review_at');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'next_review_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('revision_queue'); }
};