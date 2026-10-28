<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('reviewer_type')->default('admin_user');
            $table->uuid('reviewer_id');
            $table->jsonb('checklist');
            $table->string('decision'); // approved, rejected
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('question_reviews'); }
};