<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Tutor
{
    /**
     * Validar datos de tutor
     */
    public static function validar(array $datos)
    {
        return \Illuminate\Support\Facades\Validator::make($datos, [
            'rol' => 'nullable|in:tutor_basico,tutor_premium',
        ]);
    }

    /**
     * Crear tutor con su usuario
     */
    public static function crearConUsuario(array $datosUsuario, array $datosTutor = [])
    {
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        DB::beginTransaction();
        try {
            $userId = User::crear($datosUsuario);

            $tutorId = DB::table('tutor')->insertGetId([
                'user_id' => $userId,
                'rol' => $datosTutor['rol'] ?? 'tutor_basico',
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
    public static function actualizarConUsuario($id, array $datosUsuario = [], array $datosTutor = [])
    {
        $tutor = self::obtenerPorIdSimple($id);
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            if (!empty($datosUsuario)) {
                User::actualizar($tutor->user_id, $datosUsuario);
            }

            if (!empty($datosTutor)) {
                $datosUpdate = [];
                
                if (isset($datosTutor['rol'])) {
                    $datosUpdate['rol'] = $datosTutor['rol'];
                }

                if (!empty($datosUpdate)) {
                    $datosUpdate['updated_at'] = now();
                    DB::table('tutor')->where('id', $id)->update($datosUpdate);
                }
            }

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
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->where('tutor.id', $id)
            ->select(
                'tutor.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
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
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->select(
                'tutor.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
            );

        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('usuario.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('usuario.apellido', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['estado'])) {
            $query->where('usuario.estado', $filtros['estado']);
        }

        if (isset($filtros['rol'])) {
            $query->where('tutor.rol', $filtros['rol']);
        }

        return $query->get();
    }
}