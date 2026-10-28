<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('option_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('option_id')->constrained('question_options')->cascadeOnDelete();
            $table->string('locale', 2);
            $table->text('text');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('option_translations'); }
};