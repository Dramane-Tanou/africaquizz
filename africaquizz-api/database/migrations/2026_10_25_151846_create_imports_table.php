<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->integer('size_bytes');
            $table->string('status')->default('PARSING'); // PARSING, VALIDATED, IMPORTED, CANCELLED, FAILED
            $table->jsonb('totals')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('imports'); }
};