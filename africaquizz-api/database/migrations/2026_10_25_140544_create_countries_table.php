<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('region_id')->constrained()->cascadeOnDelete();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->string('slug')->unique();
            $table->string('capital')->nullable();
            $table->string('status')->default('member'); // member, territory, disputed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
