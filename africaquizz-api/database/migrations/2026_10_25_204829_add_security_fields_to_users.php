<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'date_of_birth')) $table->date('date_of_birth')->nullable()->after('email');
            if (!Schema::hasColumn('users', 'age')) $table->integer('age')->nullable();
            if (!Schema::hasColumn('users', 'is_minor')) $table->boolean('is_minor')->default(false);
            if (!Schema::hasColumn('users', 'parental_consent_required')) $table->boolean('parental_consent_required')->default(false);
            if (!Schema::hasColumn('users', 'parental_consent_token')) $table->string('parental_consent_token')->nullable();
            if (!Schema::hasColumn('users', 'parental_consent_confirmed_at')) $table->timestamp('parental_consent_confirmed_at')->nullable();
            if (!Schema::hasColumn('users', 'parental_email')) $table->string('parental_email')->nullable();
            if (!Schema::hasColumn('users', 'is_banned')) $table->boolean('is_banned')->default(false);
            if (!Schema::hasColumn('users', 'ban_reason')) $table->string('ban_reason')->nullable();
            if (!Schema::hasColumn('users', 'banned_at')) $table->timestamp('banned_at')->nullable();
            if (!Schema::hasColumn('users', 'is_anonymized')) $table->boolean('is_anonymized')->default(false);
        });
    }
    public function down(): void {}
};