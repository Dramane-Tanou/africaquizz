<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('password_reset_tokens')) {
            if (Schema::hasColumn('password_reset_tokens', 'user_id')) {
                return;
            }

            Schema::drop('password_reset_tokens');
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('password_reset_tokens'); }
};