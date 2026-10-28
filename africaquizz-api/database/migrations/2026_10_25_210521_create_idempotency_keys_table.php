<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('endpoint');
            $table->string('method');
            $table->uuid('user_id')->nullable();
            $table->jsonb('request_hash')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('idempotency_keys'); }
};