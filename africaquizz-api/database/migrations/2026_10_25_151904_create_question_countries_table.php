<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('question_countries', function (Blueprint $table) {
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('country_id')->constrained()->cascadeOnDelete();
            $table->primary(['question_id', 'country_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('question_countries'); }
};