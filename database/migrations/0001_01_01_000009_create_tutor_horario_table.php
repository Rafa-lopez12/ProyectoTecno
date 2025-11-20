<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutor_horario', function (Blueprint $table) {
            $table->foreignId('tutor_id')->constrained('tutor')->onDelete('cascade');
            $table->foreignId('horario_id')->constrained('horario')->onDelete('cascade');
            $table->date('fecha_asignacion')->default(now());
            $table->primary(['tutor_id', 'horario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor_horario');
    }
};