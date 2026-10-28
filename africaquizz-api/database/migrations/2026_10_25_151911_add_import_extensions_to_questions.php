<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS questions_external_id_unique ON questions (external_id) WHERE external_id IS NOT NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS qtrans_text_trgm ON question_translations USING GIN (text gin_trgm_ops)');
        }
    }
    public function down(): void {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS questions_external_id_unique');
        }

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS qtrans_text_trgm');
        }
    }
};