<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Propietario;

class PropietarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear propietario de prueba
        try {
            Propietario::crearConUsuario(
                [
                    'nombre' => 'Rafa',
                    'apellido' => 'Lopez',
                    'telefono' => '78451234',
                    'fecha_nacimiento' => '1990-01-01',
                    'direccion' => 'Oficina Principal',
                    'estado' => 'activo'
                ],
                [
                    'email' => 'rafa123@gmail.com',
                    'password' => 'leyendas13',
                    'rol' => 'admin'
                ]
            );

            $this->command->info('✅ Propietario admin creado exitosamente');
            $this->command->info('Email: rafa123@gmail.com');
            $this->command->info('Password: leyendas13');
        } catch (\Exception $e) {
            $this->command->error('Error al crear propietario: ' . $e->getMessage());
        }
    }
}