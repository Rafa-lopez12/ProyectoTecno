<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Venta
{
    public static function crear(array $datos)
    {
        return DB::table('venta')->insertGetId([
            'inscripcion_id' => $datos['inscripcion_id'],
            'propietario_id' => $datos['propietario_id'] ?? null,
            'tipo_venta' => $datos['tipo_venta'],
            'monto_total' => $datos['monto_total'],
            'monto_pagado' => $datos['monto_pagado'] ?? 0,
            'saldo_pendiente' => $datos['saldo_pendiente'],
            'mes_correspondiente' => $datos['mes_correspondiente'],
            'fecha_venta' => $datos['fecha_venta'] ?? now()->toDateString(),
            'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
            'estado' => $datos['estado'] ?? 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('venta')
            ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->join('servicio', 'inscripcion.id_servicio', '=', 'servicio.id')
            ->select(
                'venta.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre"),
                'alumno.ci as alumno_ci',
                'servicio.nombre as servicio_nombre'
            );

        if (isset($filtros['estado'])) {
            $query->where('venta.estado', $filtros['estado']);
        }

        if (isset($filtros['tipo_venta'])) {
            $query->where('venta.tipo_venta', $filtros['tipo_venta']);
        }

        if (isset($filtros['inscripcion_id'])) {
            $query->where('venta.inscripcion_id', $filtros['inscripcion_id']);
        }

        return $query->orderBy('venta.fecha_venta', 'desc')->get();
    }

    public static function reporteMensual($mes, $anio)
    {
        return DB::table('venta')
            ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->whereRaw("EXTRACT(MONTH FROM venta.fecha_venta) = ?", [$mes])
            ->whereRaw("EXTRACT(YEAR FROM venta.fecha_venta) = ?", [$anio])
            ->select(
                'venta.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre")
            )
            ->get();
    }

    public static function reportePorEstado()
    {
        return DB::table('venta')
            ->select(
                'estado',
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(monto_total) as monto_total'),
                DB::raw('SUM(monto_pagado) as monto_pagado'),
                DB::raw('SUM(saldo_pendiente) as saldo_pendiente')
            )
            ->groupBy('estado')
            ->get();
    }
}