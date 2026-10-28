<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (!Schema::hasColumn('admin_users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_enabled');
            }
        });
    }
    public function down(): void {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};