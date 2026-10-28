<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique(); // redacteur, relecteur, editeur, admin, super_admin
            $table->string('label_fr');
            $table->string('label_en');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};