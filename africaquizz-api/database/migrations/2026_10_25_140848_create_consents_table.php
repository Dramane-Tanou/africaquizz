<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('guest_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('document_type'); // terms, privacy, cookies
            $table->string('version');
            $table->timestamp('accepted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
