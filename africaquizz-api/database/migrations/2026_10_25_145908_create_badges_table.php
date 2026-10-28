<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('tier'); // bronze, argent, or
            $table->jsonb('rule_params');
            $table->timestamps();
        });
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('badge_id')->constrained()->cascadeOnDelete();
            $table->string('tier'); // Le palier effectivement obtenu
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->unique(['user_id', 'badge_id', 'tier']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};