<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Asistencia
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'inscripcion_id' => 'required|exists:inscripcion,id',
            'fecha' => 'required|date',
            'estado' => 'required|in:presente,ausente,tardanza,justificado,recuperada',
            'observaciones' => 'nullable|string'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }

        // Verificar que no exista ya una asistencia para esa inscripción en esa fecha
        $existe = DB::table('asistencia')
            ->where('inscripcion_id', $datos['inscripcion_id'])
            ->where('fecha', $datos['fecha'])
            ->exists();

        if ($existe) {
            throw new \Exception('Ya existe un registro de asistencia para esta fecha');
        }

        $id = DB::table('asistencia')->insertGetId([
            'inscripcion_id' => $datos['inscripcion_id'],
            'fecha' => $datos['fecha'],
            'estado' => $datos['estado'],
            'observaciones' => $datos['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return self::obtenerPorId($id);
    }

    public static function actualizar($id, array $datos)
    {
        $asistencia = self::obtenerPorIdSimple($id);
        
        if (!$asistencia) {
            throw new \Exception('Asistencia no encontrada');
        }

        // Verificar si tiene licencia asociada
        $tieneLicencia = DB::table('licencia')
            ->where('asistencia_id', $id)
            ->exists();

        if ($tieneLicencia && isset($datos['estado']) && $datos['estado'] !== 'ausente') {
            throw new \Exception('No se puede cambiar el estado porque tiene una licencia asociada');
        }

        $datosUpdate = [];
        
        if (isset($datos['fecha'])) {
            $datosUpdate['fecha'] = $datos['fecha'];
        }
        if (isset($datos['estado'])) {
            $datosUpdate['estado'] = $datos['estado'];
        }
        if (isset($datos['observaciones'])) {
            $datosUpdate['observaciones'] = $datos['observaciones'];
        }

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            DB::table('asistencia')->where('id', $id)->update($datosUpdate);
        }

        return self::obtenerPorId($id);
    }

    public static function eliminar($id)
    {
        $asistencia = self::obtenerPorIdSimple($id);
        
        if (!$asistencia) {
            throw new \Exception('Asistencia no encontrada');
        }

        // Verificar si tiene licencia asociada
        $tieneLicencia = DB::table('licencia')
            ->where('asistencia_id', $id)
            ->exists();

        if ($tieneLicencia) {
            throw new \Exception('No se puede eliminar porque tiene una licencia asociada');
        }

        DB::table('asistencia')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        $asistencia = DB::table('asistencia')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->leftJoin('licencia', 'asistencia.id', '=', 'licencia.asistencia_id')
            ->where('asistencia.id', $id)
            ->select(
                'asistencia.*',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre"),
                'licencia.id as licencia_id',
                'licencia.estado as licencia_estado'
            )
            ->first();

        return $asistencia;
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('asistencia')->where('id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('asistencia')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->leftJoin('licencia', 'asistencia.id', '=', 'licencia.asistencia_id')
            ->select(
                'asistencia.*',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre"),
                'licencia.id as licencia_id',
                'licencia.estado as licencia_estado'
            );

        if (isset($filtros['inscripcion_id'])) {
            $query->where('asistencia.inscripcion_id', $filtros['inscripcion_id']);
        }

        if (isset($filtros['tutor_id'])) {
            $query->where('inscripcion.tutor_id', $filtros['tutor_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('asistencia.estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('asistencia.fecha', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('asistencia.fecha', '<=', $filtros['fecha_hasta']);
        }

        // Filtrar solo ausentes (útil para mostrar las que necesitan licencia)
        if (isset($filtros['solo_ausentes']) && $filtros['solo_ausentes']) {
            $query->where('asistencia.estado', 'ausente');
        }

        // Filtrar solo las que tienen licencia
        if (isset($filtros['con_licencia']) && $filtros['con_licencia']) {
            $query->whereNotNull('licencia.id');
        }

        // Filtrar solo las que NO tienen licencia
        if (isset($filtros['sin_licencia']) && $filtros['sin_licencia']) {
            $query->whereNull('licencia.id');
        }

        return $query->orderBy('asistencia.fecha', 'desc')->get();
    }

    public static function obtenerPorInscripcion($inscripcionId)
    {
        return self::listar(['inscripcion_id' => $inscripcionId]);
    }

    public static function tieneLicencia($id)
    {
        return DB::table('licencia')
            ->where('asistencia_id', $id)
            ->exists();
    }


    public static function obtenerInforme($asistenciaId)
    {
        return DB::table('informe_clase')
            ->where('asistencia_id', $asistenciaId)
            ->first();
    }

    public static function tieneInforme($asistenciaId)
    {
        return DB::table('informe_clase')
            ->where('asistencia_id', $asistenciaId)
            ->exists();
    }
}

