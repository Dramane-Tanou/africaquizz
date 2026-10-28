<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('questions', function (Blueprint $table) {
            $table->integer('pending_reports_count')->default(0)->after('is_sensitive');
            $table->timestamp('last_reported_at')->nullable()->after('pending_reports_count');
        });
    }
    public function down(): void {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['pending_reports_count', 'last_reported_at']);
        });
    }
};