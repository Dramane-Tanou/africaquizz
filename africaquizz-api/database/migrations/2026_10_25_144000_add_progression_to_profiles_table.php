<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('profiles', function (Blueprint $table) {
            $table->integer('total_xp')->default(0)->after('is_protected');
            $table->integer('level')->default(1)->after('total_xp');
            $table->string('rank')->default('curieux')->after('level');
        });
    }
    public function down(): void {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['total_xp', 'level', 'rank']);
        });
    }
};