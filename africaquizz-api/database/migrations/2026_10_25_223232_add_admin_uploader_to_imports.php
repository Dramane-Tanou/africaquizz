<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('imports', function (Blueprint $table) {
            if (!Schema::hasColumn('imports', 'uploaded_by_admin_id')) {
                $table->foreignUuid('uploaded_by_admin_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('admin_users')
                    ->nullOnDelete();
            }
        });
    }
    public function down(): void {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_admin_id']);
            $table->dropColumn('uploaded_by_admin_id');
        });
    }
};