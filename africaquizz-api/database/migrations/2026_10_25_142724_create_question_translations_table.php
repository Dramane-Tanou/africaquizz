<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2); // fr, en
            $table->text('text');
            $table->text('explanation')->nullable();
            $table->text('key_takeaway')->nullable();
            $table->timestamps();
            $table->unique(['question_id', 'locale']);
        });
    }
    public function down(): void { Schema::dropIfExists('question_translations'); }
};