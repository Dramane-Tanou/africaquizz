<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('import_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->jsonb('raw_data');
            $table->jsonb('normalized_data')->nullable();
            $table->jsonb('errors')->nullable();
            $table->string('status'); // VALIDE, AVERTISSEMENT, ERREUR
            $table->foreignUuid('question_id')->nullable();
            $table->timestamps();
            $table->index(['import_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('import_rows'); }
};