<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('daily_challenge_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('daily_challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->integer('position');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('daily_challenge_questions'); }
};