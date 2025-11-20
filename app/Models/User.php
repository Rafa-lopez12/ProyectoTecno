<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class User
{
    /**
     * Validar datos de usuario
     */
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            'estado' => 'nullable|in:activo,inactivo,suspendido',
        ]);
    }

    /**
     * Crear usuario en la base de datos
     */
    public static function crear(array $datos)
    {
        return DB::table('usuario')->insertGetId([
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'telefono' => $datos['telefono'] ?? null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'estado' => $datos['estado'] ?? 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Actualizar usuario
     */
    public static function actualizar($id, array $datos)
    {
        $datosUpdate = [];
        
        if (isset($datos['nombre'])) $datosUpdate['nombre'] = $datos['nombre'];
        if (isset($datos['apellido'])) $datosUpdate['apellido'] = $datos['apellido'];
        if (isset($datos['telefono'])) $datosUpdate['telefono'] = $datos['telefono'];
        if (isset($datos['fecha_nacimiento'])) $datosUpdate['fecha_nacimiento'] = $datos['fecha_nacimiento'];
        if (isset($datos['direccion'])) $datosUpdate['direccion'] = $datos['direccion'];
        if (isset($datos['estado'])) $datosUpdate['estado'] = $datos['estado'];

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            return DB::table('usuario')->where('id', $id)->update($datosUpdate);
        }

        return 0;
    }

    /**
     * Eliminar usuario (soft delete - cambiar estado a inactivo)
     */
    public static function eliminar($id)
    {
        return DB::table('usuario')->where('id', $id)->update([
            'estado' => 'inactivo',
            'updated_at' => now()
        ]);
    }

    /**
     * Obtener usuario por ID
     */
    public static function obtenerPorId($id)
    {
        return DB::table('usuario')->where('id', $id)->first();
    }

    /**
     * Verificar si un usuario está activo
     */
    public static function estaActivo($id)
    {
        $user = self::obtenerPorId($id);
        return $user && $user->estado === 'activo';
    }

    /**
     * Cambiar estado de usuario
     */
    public static function cambiarEstado($id, $nuevoEstado)
    {
        $estados = ['activo', 'inactivo', 'suspendido'];
        
        if (!in_array($nuevoEstado, $estados)) {
            throw new \Exception('Estado inválido');
        }

        return DB::table('usuario')->where('id', $id)->update([
            'estado' => $nuevoEstado,
            'updated_at' => now()
        ]);
    }

    public static function obtenerNombreCompleto($user)
    {
        if (is_object($user)) {
            return $user->nombre . ' ' . $user->apellido;
        }
        
        $userData = self::obtenerPorId($user);
        return $userData ? $userData->nombre . ' ' . $userData->apellido : '';
    }
}