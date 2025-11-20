<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_clase', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            
            // Datos de la clase
            $table->date('fecha');
            $table->text('temas_vistos');
            $table->text('tareas_asignadas')->nullable();
            
            // Progreso del alumno
            $table->string('nivel_comprension', 20)->nullable(); // excelente, bueno, regular, necesita_refuerzo
            $table->string('participacion', 20)->nullable(); // alta, media, baja
            $table->string('cumplimiento_tareas', 20)->nullable(); // completo, parcial, no_cumplido
            $table->decimal('calificacion', 5, 2)->nullable();
            
            // Informe para padres
            $table->text('resumen')->nullable();
            $table->text('logros')->nullable();
            $table->text('dificultades')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->text('observaciones')->nullable();
            
            // Control
            $table->string('estado', 20)->default('realizada'); // realizada, cancelada, reprogramada
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('inscripcion_id')->references('id')->on('inscripcion')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['inscripcion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_clase');
    }
};