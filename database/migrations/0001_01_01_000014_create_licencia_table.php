// database/migrations/0001_01_01_000014_create_licencia_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asistencia_id');
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('asistencia_id')->references('id')->on('asistencia')->onDelete('cascade');
            
            // Unique constraint - una asistencia solo puede tener una licencia
            $table->unique('asistencia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia');
    }
};