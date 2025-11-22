<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Reprogramacion
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'licencia_id' => 'required|exists:licencia,id',
            'fecha_original' => 'required|date',
            'fecha_nueva' => 'required|date|after:fecha_original',
            'estado' => 'nullable|in:programada,realizada,cancelada',
            'observaciones' => 'nullable|string'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }

        // Verificar que la licencia existe y está aprobada
        $licencia = DB::table('licencia')->where('id', $datos['licencia_id'])->first();
        
        if (!$licencia) {
            throw new \Exception('Licencia no encontrada');
        }

        if ($licencia->estado !== 'aprobada') {
            throw new \Exception('Solo se pueden crear reprogramaciones para licencias aprobadas');
        }

        // Si no se proporciona fecha_original, obtenerla de la asistencia
        if (!isset($datos['fecha_original'])) {
            $asistencia = DB::table('asistencia')->where('id', $licencia->asistencia_id)->first();
            $datos['fecha_original'] = $asistencia->fecha;
        }

        $id = DB::table('reprogramacion')->insertGetId([
            'licencia_id' => $datos['licencia_id'],
            'fecha_original' => $datos['fecha_original'],
            'fecha_nueva' => $datos['fecha_nueva'],
            'estado' => $datos['estado'] ?? 'programada',
            'observaciones' => $datos['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return self::obtenerPorId($id);
    }

    public static function actualizar($id, array $datos)
    {
        $reprogramacion = self::obtenerPorIdSimple($id);
        
        if (!$reprogramacion) {
            throw new \Exception('Reprogramación no encontrada');
        }

        $datosUpdate = [];
        
        if (isset($datos['fecha_original'])) {
            $datosUpdate['fecha_original'] = $datos['fecha_original'];
        }
        if (isset($datos['fecha_nueva'])) {
            $datosUpdate['fecha_nueva'] = $datos['fecha_nueva'];
        }
        if (isset($datos['estado'])) {
            $datosUpdate['estado'] = $datos['estado'];
        }
        if (isset($datos['observaciones'])) {
            $datosUpdate['observaciones'] = $datos['observaciones'];
        }

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            DB::table('reprogramacion')->where('id', $id)->update($datosUpdate);
        }

        return self::obtenerPorId($id);
    }

    public static function eliminar($id)
    {
        $reprogramacion = self::obtenerPorIdSimple($id);
        
        if (!$reprogramacion) {
            throw new \Exception('Reprogramación no encontrada');
        }

        if ($reprogramacion->estado === 'realizada') {
            throw new \Exception('No se puede eliminar una reprogramación ya realizada');
        }

        DB::table('reprogramacion')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('reprogramacion')
            ->join('licencia', 'reprogramacion.licencia_id', '=', 'licencia.id')
            ->join('asistencia', 'licencia.asistencia_id', '=', 'asistencia.id')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->where('reprogramacion.id', $id)
            ->select(
                'reprogramacion.*',
                'licencia.motivo as licencia_motivo',
                'licencia.estado as licencia_estado',
                'asistencia.id as asistencia_id',
                'inscripcion.id as inscripcion_id',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('reprogramacion')->where('id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('reprogramacion')
            ->join('licencia', 'reprogramacion.licencia_id', '=', 'licencia.id')
            ->join('asistencia', 'licencia.asistencia_id', '=', 'asistencia.id')
            ->join('inscripcion', 'asistencia.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->select(
                'reprogramacion.*',
                'licencia.motivo as licencia_motivo',
                'inscripcion.id as inscripcion_id',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            );

        if (isset($filtros['licencia_id'])) {
            $query->where('reprogramacion.licencia_id', $filtros['licencia_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('reprogramacion.estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('reprogramacion.fecha_nueva', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('reprogramacion.fecha_nueva', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('reprogramacion.fecha_nueva', 'desc')->get();
    }

    public static function marcarRealizada($id)
    {
        $reprogramacion = self::obtenerPorIdSimple($id);
        
        if (!$reprogramacion) {
            throw new \Exception('Reprogramación no encontrada');
        }
    
        if ($reprogramacion->estado !== 'programada') {
            throw new \Exception('Solo se pueden marcar como realizadas las reprogramaciones programadas');
        }
    
        DB::beginTransaction();
        try {
          
            DB::table('reprogramacion')->where('id', $id)->update([
                'estado' => 'realizada',
                'updated_at' => now()
            ]);
    
            $licencia = DB::table('licencia')->where('id', $reprogramacion->licencia_id)->first();
            
            if ($licencia) {
          
                DB::table('asistencia')->where('id', $licencia->asistencia_id)->update([
                    'estado' => 'recuperada',
                    'updated_at' => now()
                ]);
            }
    
            DB::commit();
            return self::obtenerPorId($id);
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function cancelar($id)
    {
        $reprogramacion = self::obtenerPorIdSimple($id);
        
        if (!$reprogramacion) {
            throw new \Exception('Reprogramación no encontrada');
        }

        if ($reprogramacion->estado === 'realizada') {
            throw new \Exception('No se puede cancelar una reprogramación ya realizada');
        }

        DB::table('reprogramacion')->where('id', $id)->update([
            'estado' => 'cancelada',
            'updated_at' => now()
        ]);

        return self::obtenerPorId($id);
    }
}