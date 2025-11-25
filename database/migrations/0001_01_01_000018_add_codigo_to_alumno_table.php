<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero agregar la columna como nullable
        Schema::table('alumno', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable()->after('ci');
        });

        // Generar códigos para alumnos existentes
        $alumnos = DB::table('alumno')->whereNull('codigo')->get();
        
        foreach ($alumnos as $alumno) {
            do {
                // Generar código: ALU + 5 dígitos aleatorios
                $codigo = 'ALU' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
                
                // Verificar que no exista
                $existe = DB::table('alumno')->where('codigo', $codigo)->exists();
            } while ($existe);

            DB::table('alumno')
                ->where('id', $alumno->id)
                ->update(['codigo' => $codigo]);
        }

        // Ahora hacer la columna NOT NULL y UNIQUE
        Schema::table('alumno', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};