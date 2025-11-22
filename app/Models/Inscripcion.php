<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Venta;

class Inscripcion
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'id_servicio' => 'required|exists:servicio,id',
            'alumno_id' => 'required|exists:alumno,id',
            'tutor_id' => 'required|exists:tutor,id',
            'fecha_inscripcion' => 'nullable|date',
            'estado' => 'nullable|in:activo,retirado,finalizado',
            'observaciones' => 'nullable|string'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }
    
        DB::beginTransaction();
        try {
            $id = DB::table('inscripcion')->insertGetId([
                'id_servicio' => $datos['id_servicio'],
                'alumno_id' => $datos['alumno_id'],
                'tutor_id' => $datos['tutor_id'],
                'fecha_inscripcion' => $datos['fecha_inscripcion'] ?? now()->toDateString(),
                'estado' => $datos['estado'] ?? 'activo',
                'observaciones' => $datos['observaciones'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            // Crear venta automáticamente
            if (isset($datos['crear_venta']) && $datos['crear_venta']) {
                Venta::crear([
                    'inscripcion_id' => $id,
                    'propietario_id' => $datos['propietario_id'] ?? null,
                    'tipo_venta' => $datos['tipo_venta'] ?? 'contado',
                    'monto_total' => $datos['monto_total'] ?? 0,
                    'monto_pagado' => $datos['monto_pagado'] ?? 0,
                    'saldo_pendiente' => ($datos['monto_total'] ?? 0) - ($datos['monto_pagado'] ?? 0),
                    'mes_correspondiente' => $datos['mes_correspondiente'] ?? date('F Y'),
                    'fecha_venta' => $datos['fecha_venta'] ?? now()->toDateString(),
                    'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
                ]);
            }
    
            DB::commit();
            return self::obtenerPorId($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function actualizar($id, array $datos)
    {
        $inscripcion = self::obtenerPorIdSimple($id);
        
        if (!$inscripcion) {
            throw new \Exception('Inscripción no encontrada');
        }

        $datosUpdate = [];
        
        if (isset($datos['id_servicio'])) {
            $datosUpdate['id_servicio'] = $datos['id_servicio'];
        }
        
        if (isset($datos['alumno_id'])) {
            $datosUpdate['alumno_id'] = $datos['alumno_id'];
        }
        
        if (isset($datos['tutor_id'])) {
            $datosUpdate['tutor_id'] = $datos['tutor_id'];
        }
        
        if (isset($datos['fecha_inscripcion'])) {
            $datosUpdate['fecha_inscripcion'] = $datos['fecha_inscripcion'];
        }
        
        if (isset($datos['estado'])) {
            $datosUpdate['estado'] = $datos['estado'];
        }
        
        if (isset($datos['observaciones'])) {
            $datosUpdate['observaciones'] = $datos['observaciones'];
        }

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            DB::table('inscripcion')->where('id', $id)->update($datosUpdate);
        }

        return self::obtenerPorId($id);
    }

    public static function eliminar($id)
    {
        $inscripcion = self::obtenerPorIdSimple($id);
        
        if (!$inscripcion) {
            throw new \Exception('Inscripción no encontrada');
        }

        DB::table('inscripcion')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('inscripcion')
            ->join('servicio', 'inscripcion.id_servicio', '=', 'servicio.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->where('inscripcion.id', $id)
            ->select(
                'inscripcion.*',
                'servicio.nombre as servicio_nombre',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('inscripcion')->where('id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('inscripcion')
            ->join('servicio', 'inscripcion.id_servicio', '=', 'servicio.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('tutor', 'inscripcion.tutor_id', '=', 'tutor.id')
            ->join('usuario as u_alumno', 'alumno.user_id', '=', 'u_alumno.id')
            ->join('usuario as u_tutor', 'tutor.user_id', '=', 'u_tutor.id')
            ->select(
                'inscripcion.*',
                'servicio.nombre as servicio_nombre',
                DB::raw("CONCAT(u_alumno.nombre, ' ', u_alumno.apellido) as alumno_nombre"),
                DB::raw("CONCAT(u_tutor.nombre, ' ', u_tutor.apellido) as tutor_nombre")
            );

        if (isset($filtros['estado'])) {
            $query->where('inscripcion.estado', $filtros['estado']);
        }

        if (isset($filtros['alumno_id'])) {
            $query->where('inscripcion.alumno_id', $filtros['alumno_id']);
        }

        if (isset($filtros['tutor_id'])) {
            $query->where('inscripcion.tutor_id', $filtros['tutor_id']);
        }

        if (isset($filtros['servicio_id'])) {
            $query->where('inscripcion.id_servicio', $filtros['servicio_id']);
        }

        return $query->orderBy('inscripcion.created_at', 'desc')->get();
    }

    public static function obtenerInformes($id)
    {
        return DB::table('informe_clase')
            ->where('inscripcion_id', $id)
            ->orderBy('fecha', 'desc')
            ->get();
    }
}