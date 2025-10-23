<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Alumno
{
    /**
     * Validar datos de alumno
     */
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'grado_escolar' => 'nullable|string|max:50',
            'fecha_ingreso' => 'required|date',
        ]);
    }

    /**
     * Crear alumno con su usuario
     */
    public static function crearConUsuario(array $datosUsuario, array $datosAlumno)
    {
        // Validar datos de usuario
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        // Validar datos de alumno
        $validatorAlumno = self::validar($datosAlumno);
        if ($validatorAlumno->fails()) {
            throw new \Exception('Error en datos de alumno: ' . $validatorAlumno->errors()->first());
        }

        DB::beginTransaction();
        try {
            // Crear usuario
            $userId = User::crear($datosUsuario);

            // Crear alumno
            $alumnoId = DB::table('alumno')->insertGetId([
                'user_id' => $userId,
                'grado_escolar' => $datosAlumno['grado_escolar'] ?? null,
                'fecha_ingreso' => $datosAlumno['fecha_ingreso'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return self::obtenerPorId($alumnoId);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar alumno y su usuario
     */
    public static function actualizarConUsuario($id, array $datosUsuario = [], array $datosAlumno = [])
    {
        $alumno = self::obtenerPorIdSimple($id);
        
        if (!$alumno) {
            throw new \Exception('Alumno no encontrado');
        }

        DB::beginTransaction();
        try {
            // Actualizar usuario si hay datos
            if (!empty($datosUsuario)) {
                User::actualizar($alumno->user_id, $datosUsuario);
            }

            // Actualizar alumno si hay datos
            if (!empty($datosAlumno)) {
                $datosUpdate = [];
                
                if (isset($datosAlumno['grado_escolar'])) {
                    $datosUpdate['grado_escolar'] = $datosAlumno['grado_escolar'];
                }

                if (!empty($datosUpdate)) {
                    $datosUpdate['updated_at'] = now();
                    DB::table('alumno')->where('id', $id)->update($datosUpdate);
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
     * Eliminar alumno y su usuario
     */
    public static function eliminarConUsuario($id)
    {
        $alumno = self::obtenerPorIdSimple($id);
        
        if (!$alumno) {
            throw new \Exception('Alumno no encontrado');
        }

        DB::beginTransaction();
        try {
            $userId = $alumno->user_id;
            
            DB::table('alumno')->where('id', $id)->delete();
            User::eliminar($userId);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener alumno por ID con datos de usuario
     */
    public static function obtenerPorId($id)
    {
        return DB::table('alumno')
            ->join('user', 'alumno.user_id', '=', 'user.id')
            ->where('alumno.id', $id)
            ->select(
                'alumno.*',
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
     * Obtener alumno por ID (solo tabla alumno)
     */
    public static function obtenerPorIdSimple($id)
    {
        return DB::table('alumno')->where('id', $id)->first();
    }

    
    /**
     * Listar todos los alumnos
     */
    public static function listar($filtros = [])
    {
        $query = DB::table('alumno')
            ->join('user', 'alumno.user_id', '=', 'user.id')
            ->select(
                'alumno.*',
                'user.nombre',
                'user.apellido',
                'user.telefono',
                'user.fecha_nacimiento',
                'user.direccion',
                'user.estado'
            );

        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('user.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('user.apellido', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['grado_escolar'])) {
            $query->where('alumno.grado_escolar', $filtros['grado_escolar']);
        }

        if (isset($filtros['estado'])) {
            $query->where('user.estado', $filtros['estado']);
        }

        return $query->get();
    }
}