<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 1); // A, B, C, D
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('question_options'); }
};