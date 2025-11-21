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
            'fecha_nueva' => 'required|date',
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

        DB::table('reprogramacion')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('reprogramacion')
            ->join('licencia', 'reprogramacion.licencia_id', '=', 'licencia.id')
            ->join('tutor', 'licencia.tutor_id', '=', 'tutor.id')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->where('reprogramacion.id', $id)
            ->select(
                'reprogramacion.*',
                'licencia.motivo as licencia_motivo',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as tutor_nombre")
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
            ->join('tutor', 'licencia.tutor_id', '=', 'tutor.id')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->select(
                'reprogramacion.*',
                'licencia.motivo as licencia_motivo',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as tutor_nombre")
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
        return DB::table('reprogramacion')->where('id', $id)->update([
            'estado' => 'realizada',
            'updated_at' => now()
        ]);
    }

    public static function cancelar($id)
    {
        return DB::table('reprogramacion')->where('id', $id)->update([
            'estado' => 'cancelada',
            'updated_at' => now()
        ]);
    }
}