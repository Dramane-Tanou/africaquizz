<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('actor_type'); // admin_user, user, system
            $table->uuid('actor_id')->nullable();
            $table->string('action'); // question.transition, question.import, etc.
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('action');
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};