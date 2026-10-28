<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_session_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('amount'); // Positif pour gain, négatif pour correction
            $table->string('reason'); // quiz_completion, daily_bonus, correction
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('xp_transactions'); }
};