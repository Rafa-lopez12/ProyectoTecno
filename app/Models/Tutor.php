<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Tutor
{
    /**
     * Crear tutor con su usuario
     */
    public static function crearConUsuario(array $datosUsuario)
    {
        // Validar datos de usuario
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        DB::beginTransaction();
        try {
            // Crear usuario
            $userId = User::crear($datosUsuario);

            // Crear tutor
            $tutorId = DB::table('tutor')->insertGetId([
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return self::obtenerPorId($tutorId);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar tutor y su usuario
     */
    public static function actualizarConUsuario($id, array $datosUsuario = [])
    {
        $tutor = self::obtenerPorIdSimple($id);
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            // Actualizar usuario si hay datos
            if (!empty($datosUsuario)) {
                User::actualizar($tutor->user_id, $datosUsuario);
            }

            // Actualizar tutor (timestamp)
            DB::table('tutor')
                ->where('id', $id)
                ->update(['updated_at' => now()]);

            DB::commit();

            return self::obtenerPorId($id);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar tutor y su usuario
     */
    public static function eliminarConUsuario($id)
    {
        $tutor = self::obtenerPorIdSimple($id);
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            $userId = $tutor->user_id;

            DB::table('tutor')->where('id', $id)->delete();
            User::eliminar($userId);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener tutor por ID con datos de usuario
     */
    public static function obtenerPorId($id)
    {
        return DB::table('tutor')
            ->join('user', 'tutor.user_id', '=', 'user.id')
            ->where('tutor.id', $id)
            ->select(
                'tutor.*',
                'user.nombre',
                'user.apellido',
                'user.telefono',
                'user.fecha_nacimiento',
                'user.direccion',
                'user.estado'
            )
            ->first();
    }

    /**
     * Obtener tutor por ID (solo tabla tutor)
     */
    public static function obtenerPorIdSimple($id)
    {
        return DB::table('tutor')->where('id', $id)->first();
    }

    /**
     * Listar todos los tutores
     */
    public static function listar($filtros = [])
    {
        $query = DB::table('tutor')
            ->join('user', 'tutor.user_id', '=', 'user.id')
            ->select(
                'tutor.*',
                'user.nombre',
                'user.apellido',
                'user.telefono',
                'user.fecha_nacimiento',
                'user.direccion',
                'user.estado'
            );

        // Aplicar filtros
        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('user.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('user.apellido', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['estado'])) {
            $query->where('user.estado', $filtros['estado']);
        }

        return $query->get();
    }
}