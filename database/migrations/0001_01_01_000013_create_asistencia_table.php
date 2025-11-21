<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->date('fecha');
            $table->string('estado', 20); // presente, ausente, tardanza, justificado
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('inscripcion_id')->references('id')->on('inscripcion')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['inscripcion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};