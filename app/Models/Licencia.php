<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Licencia
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'asistencia_id' => 'required|exists:asistencia,id',
            'motivo' => 'required|string',
            'estado' => 'nullable|in:pendiente,aprobada,rechazada'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }

        // Verificar que la asistencia existe y es "ausente"
        $asistencia = DB::table('asistencia')->where('id', $datos['asistencia_id'])->first();
        
        if (!$asistencia) {
            throw new \Exception('Asistencia no encontrada');
        }

        if ($asistencia->estado !== 'ausente') {
            throw new \Exception('Solo se puede crear licencia para asistencias ausentes');
        }

        // Verificar que no tenga ya una licencia
        $existeLicencia = DB::table('licencia')
            ->where('asistencia_id', $datos['asistencia_id'])
            ->exists();

        if ($existeLicencia) {
            throw new \Exception('Esta asistencia ya tiene una licencia asociada');
        }

        $id = DB::table('licencia')->insertGetId([
            'asistencia_id' => $datos['asistencia_id'],
            'motivo' => $datos['motivo'],
            'estado' => $datos['estado'] ?? 'pendiente',
            'fecha_solicitud' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return self::obtenerPorId($id);
    }

    public static function actualizar($id, array $datos)
    {
        $licencia = self::obtenerPorIdSimple($id);
        
        if (!$licencia) {
            throw new \Exception('Licencia no encontrada');
        }

        $datosUpdate = [];
        
        if (isset($datos['motivo'])) {
            $datosUpdate['motivo'] = $datos['motivo'];
        }
        
        if (isset($datos['estado'])) {
            $datosUpdate['estado'] = $datos['estado'];
        }

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            DB::table('licencia')->where('id', $id)->update($datosUpdate);
        }

        return self::obtenerPorId($id);
    }

    public static function eliminar($id)
    {
        $licencia = self::obtenerPorIdSimple($id);
        
        if (!$licencia) {
            throw new \Exception('Licencia no encontrada');
        }

        // Verificar si tiene reprogramaciones
        $tieneReprogramaciones = DB::table('reprogramacion')
            ->where('licencia_id', $id)
            ->exists();

        if ($tieneReprogramaciones) {
            throw new \Exception('No se puede eliminar porque tiene reprogramaciones asociadas');
        }

        DB::table('licencia')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('licencia')
            ->join('asistencia', 'licencia.asistencia_id', '=', 'asistencia.id')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->where('licencia.id', $id)
            ->select(
                'licencia.*',
                'asistencia.fecha as fecha_asistencia',
                'asistencia.estado as estado_asistencia',
                'inscripcion.id as inscripcion_id',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('licencia')->where('id', $id)->first();
    }

    public static function obtenerPorAsistencia($asistenciaId)
    {
        $licencia = DB::table('licencia')->where('asistencia_id', $asistenciaId)->first();
        
        if (!$licencia) {
            return null;
        }

        return self::obtenerPorId($licencia->id);
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('licencia')
            ->join('asistencia', 'licencia.asistencia_id', '=', 'asistencia.id')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->select(
                'licencia.*',
                'asistencia.fecha as fecha_asistencia',
                'inscripcion.id as inscripcion_id',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            );

        if (isset($filtros['asistencia_id'])) {
            $query->where('licencia.asistencia_id', $filtros['asistencia_id']);
        }

        if (isset($filtros['tutor_id'])) {
            $query->where('inscripcion.tutor_id', $filtros['tutor_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('licencia.estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('asistencia.fecha', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('asistencia.fecha', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('licencia.fecha_solicitud', 'desc')->get();
    }

    public static function aprobar($id)
    {
        $licencia = self::obtenerPorIdSimple($id);
        
        if (!$licencia) {
            throw new \Exception('Licencia no encontrada');
        }

        if ($licencia->estado !== 'pendiente') {
            throw new \Exception('Solo se pueden aprobar licencias pendientes');
        }

        DB::table('licencia')->where('id', $id)->update([
            'estado' => 'aprobada',
            'updated_at' => now()
        ]);

        return self::obtenerPorId($id);
    }

    public static function rechazar($id)
    {
        $licencia = self::obtenerPorIdSimple($id);
        
        if (!$licencia) {
            throw new \Exception('Licencia no encontrada');
        }

        if ($licencia->estado !== 'pendiente') {
            throw new \Exception('Solo se pueden rechazar licencias pendientes');
        }

        DB::table('licencia')->where('id', $id)->update([
            'estado' => 'rechazada',
            'updated_at' => now()
        ]);

        return self::obtenerPorId($id);
    }

    public static function obtenerReprogramaciones($licenciaId)
    {
        return DB::table('reprogramacion')
            ->where('licencia_id', $licenciaId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}