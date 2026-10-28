<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('leagues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('week_key', 10)->unique(); // ex: 2026-W43
            $table->string('tier'); // bronze, argent, or, diamant
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
        });

        Schema::create('league_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('league_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->integer('xp_earned')->default(0);
            $table->integer('final_rank')->nullable();
            $table->boolean('promoted')->default(false);
            $table->boolean('relegated')->default(false);
            $table->timestamps();
            $table->unique(['league_id', 'user_id']);
            $table->index(['league_id', 'xp_earned']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('league_members');
        Schema::dropIfExists('leagues');
    }
};