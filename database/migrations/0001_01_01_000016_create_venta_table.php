
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->unsignedBigInteger('propietario_id')->nullable();
            $table->string('tipo_venta', 20);
            $table->decimal('monto_total', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->string('mes_correspondiente', 20);
            $table->date('fecha_venta')->default(DB::raw('CURRENT_DATE'));
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();
            
            $table->foreign('inscripcion_id')->references('id')->on('inscripcion')->onDelete('cascade');
            $table->foreign('propietario_id')->references('id')->on('propietario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};