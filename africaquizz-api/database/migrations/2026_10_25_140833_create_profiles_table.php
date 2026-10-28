<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('pseudo')->unique();
            $table->string('avatar')->default('default');
            $table->foreignUuid('country_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('birth_year')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale', 5)->default('fr');
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
