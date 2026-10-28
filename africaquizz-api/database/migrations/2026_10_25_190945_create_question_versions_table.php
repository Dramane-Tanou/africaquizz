<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->jsonb('snapshot');
            $table->string('created_by_type')->default('admin_user');
            $table->uuid('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['question_id', 'version_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('question_versions'); }
};