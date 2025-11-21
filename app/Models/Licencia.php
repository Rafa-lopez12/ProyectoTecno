<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Licencia
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'tutor_id' => 'required|exists:tutor,id',
            'fecha_licencia' => 'required|date',
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

        $id = DB::table('licencia')->insertGetId([
            'tutor_id' => $datos['tutor_id'],
            'fecha_licencia' => $datos['fecha_licencia'],
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
        
        if (isset($datos['fecha_licencia'])) {
            $datosUpdate['fecha_licencia'] = $datos['fecha_licencia'];
        }
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

        DB::table('licencia')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('licencia')
            ->join('tutor', 'licencia.tutor_id', '=', 'tutor.id')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->where('licencia.id', $id)
            ->select(
                'licencia.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as tutor_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('licencia')->where('id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('licencia')
            ->join('tutor', 'licencia.tutor_id', '=', 'tutor.id')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->select(
                'licencia.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as tutor_nombre")
            );

        if (isset($filtros['tutor_id'])) {
            $query->where('licencia.tutor_id', $filtros['tutor_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('licencia.estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('licencia.fecha_licencia', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('licencia.fecha_licencia', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('licencia.fecha_solicitud', 'desc')->get();
    }

    public static function aprobar($id)
    {
        return DB::table('licencia')->where('id', $id)->update([
            'estado' => 'aprobada',
            'updated_at' => now()
        ]);
    }

    public static function rechazar($id)
    {
        return DB::table('licencia')->where('id', $id)->update([
            'estado' => 'rechazada',
            'updated_at' => now()
        ]);
    }

    public static function obtenerReprogramaciones($licenciaId)
    {
        return DB::table('reprogramacion')
            ->where('licencia_id', $licenciaId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}