<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->default('level_1'); // level_1, level_2, level_3
            $table->string('publisher')->nullable();
            $table->string('title');
            $table->text('url');
            $table->timestamp('accessed_at')->nullable();
            $table->string('link_status')->default('unknown'); // ok, broken, unknown
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('question_sources'); }
};