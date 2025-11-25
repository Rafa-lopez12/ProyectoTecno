<?php
// database/migrations/0001_01_01_000017_add_horarios_to_inscripcion_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->json('horarios')->nullable()->after('tutor_id');
        });
    }

    public function down(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropColumn('horarios');
        });
    }
};