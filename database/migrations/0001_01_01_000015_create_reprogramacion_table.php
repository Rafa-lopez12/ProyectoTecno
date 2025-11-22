// database/migrations/0001_01_01_000015_create_reprogramacion_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reprogramacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licencia_id');
            $table->date('fecha_original');
            $table->date('fecha_nueva');
            $table->enum('estado', ['programada', 'realizada', 'cancelada'])->default('programada');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key
            $table->foreign('licencia_id')->references('id')->on('licencia')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reprogramacion');
    }
};