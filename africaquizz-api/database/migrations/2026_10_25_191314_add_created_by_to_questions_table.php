<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('created_by_type')->default('admin_user')->after('status');
            $table->uuid('created_by_id')->nullable()->after('created_by_type');
        });
    }
    public function down(): void {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['created_by_type', 'created_by_id']);
        });
    }
};