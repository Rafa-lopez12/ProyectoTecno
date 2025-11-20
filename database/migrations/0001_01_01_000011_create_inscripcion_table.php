<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripcion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_servicio');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('tutor_id');
            
            $table->date('fecha_inscripcion')->default(DB::raw('CURRENT_DATE'));
            $table->string('estado', 20)->default('activo'); // activo, retirado, finalizado
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('id_servicio')->references('id')->on('servicio')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumno')->onDelete('cascade');
            $table->foreign('tutor_id')->references('id')->on('tutor')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion');
    }
};