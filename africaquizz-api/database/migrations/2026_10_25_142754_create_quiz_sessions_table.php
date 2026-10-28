<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('guest_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('mode'); // classic, rapid, daily
            $table->string('status')->default('active'); // active, finished
            $table->integer('score')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_sessions'); }
};