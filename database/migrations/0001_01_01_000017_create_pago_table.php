<?php
// database/migrations/0001_01_01_000017_create_pago_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('venta')->onDelete('cascade');
            $table->decimal('monto', 10, 2);
            $table->timestamp('fecha_pago')->useCurrent();
            $table->string('metodo_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('propietario')->onDelete('set null');
            
            // Campos para PagoFácil
            $table->string('pagofacil_transaction_id')->nullable()->unique();
            $table->string('company_transaction_id')->nullable()->unique();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};