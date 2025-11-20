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
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        $validatorAlumno = self::validar($datosAlumno);
        if ($validatorAlumno->fails()) {
            throw new \Exception('Error en datos de alumno: ' . $validatorAlumno->errors()->first());
        }

        DB::beginTransaction();
        try {
            $userId = User::crear($datosUsuario);

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
            if (!empty($datosUsuario)) {
                User::actualizar($alumno->user_id, $datosUsuario);
            }

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

   
    public static function eliminarConUsuario($id)
    {
        $alumno = self::obtenerPorIdSimple($id);
        
        if (!$alumno) {
            throw new \Exception('Alumno no encontrado');
        }

        DB::beginTransaction();
        try {
            // Cambiar estado del usuario a inactivo en lugar de eliminar
            DB::table('usuario')
                ->where('id', $alumno->user_id)
                ->update([
                    'estado' => 'inactivo',
                    'updated_at' => now()
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

   
    public static function obtenerPorId($id)
    {
        return DB::table('alumno')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->where('alumno.id', $id)
            ->select(
                'alumno.*',
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
     * Obtener alumno por ID (solo tabla alumno)
     */
    public static function obtenerPorIdSimple($id)
    {
        return DB::table('alumno')->where('id', $id)->first();
    }

    

    public static function listar($filtros = [])
    {
        $query = DB::table('alumno')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->select(
                'alumno.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
            );

        // Por defecto solo mostrar activos, a menos que se especifique lo contrario
        if (!isset($filtros['mostrar_inactivos']) || !$filtros['mostrar_inactivos']) {
            $query->where('usuario.estado', 'activo');
        }

        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('usuario.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('usuario.apellido', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['grado_escolar'])) {
            $query->where('alumno.grado_escolar', $filtros['grado_escolar']);
        }

        if (isset($filtros['estado'])) {
            $query->where('usuario.estado', $filtros['estado']);
        }

        return $query->get();
    }
}