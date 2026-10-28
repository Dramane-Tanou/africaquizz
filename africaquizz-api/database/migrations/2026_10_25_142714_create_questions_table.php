<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('mcq'); // mcq, true_false, etc.
            $table->string('difficulty'); // decouverte, connaisseur, expert, legende
            $table->integer('time_limit_s')->default(20);
            $table->boolean('is_sensitive')->default(false);
            $table->string('status')->default('BROUILLON'); // BROUILLON, A_VERIFIER, VALIDEE, PUBLIEE
            $table->string('external_id')->nullable()->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('questions'); }
};