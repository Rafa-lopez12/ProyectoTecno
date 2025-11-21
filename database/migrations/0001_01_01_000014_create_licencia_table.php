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
            $table->unsignedBigInteger('tutor_id');
            $table->date('fecha_licencia');
            $table->text('motivo');
            $table->string('estado', 20)->default('pendiente'); // pendiente, aprobada, rechazada
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('tutor_id')->references('id')->on('tutor')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia');
    }
};