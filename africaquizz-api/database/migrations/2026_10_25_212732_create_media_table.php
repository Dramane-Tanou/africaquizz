<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('filename');
                $table->string('path');
                $table->string('disk')->default('public');
                $table->string('mime_type');
                $table->integer('width');
                $table->integer('height');
                $table->integer('size_bytes');
                $table->string('hash', 64)->unique();
                $table->string('blurhash')->nullable();
                $table->string('license')->nullable();
                $table->string('author')->nullable();
                $table->text('source_url')->nullable();
                $table->text('attribution_text')->nullable();
                $table->text('alt_text_fr')->nullable();
                $table->text('alt_text_en')->nullable();
                $table->foreignUuid('uploaded_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('media'); }
};