<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InformeClase
{
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'inscripcion_id' => 'required|exists:inscripcion,id',
            'fecha' => 'required|date',
            'temas_vistos' => 'required|string',
            'tareas_asignadas' => 'nullable|string',
            'nivel_comprension' => 'nullable|in:excelente,bueno,regular,necesita_refuerzo',
            'participacion' => 'nullable|in:alta,media,baja',
            'cumplimiento_tareas' => 'nullable|in:completo,parcial,no_cumplido',
            'calificacion' => 'nullable|numeric|min:0|max:100',
            'resumen' => 'nullable|string',
            'logros' => 'nullable|string',
            'dificultades' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'estado' => 'nullable|in:realizada,cancelada,reprogramada'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }

        $id = DB::table('informe_clase')->insertGetId([
            'inscripcion_id' => $datos['inscripcion_id'],
            'fecha' => $datos['fecha'],
            'temas_vistos' => $datos['temas_vistos'],
            'tareas_asignadas' => $datos['tareas_asignadas'] ?? null,
            'nivel_comprension' => $datos['nivel_comprension'] ?? null,
            'participacion' => $datos['participacion'] ?? null,
            'cumplimiento_tareas' => $datos['cumplimiento_tareas'] ?? null,
            'calificacion' => $datos['calificacion'] ?? null,
            'resumen' => $datos['resumen'] ?? null,
            'logros' => $datos['logros'] ?? null,
            'dificultades' => $datos['dificultades'] ?? null,
            'recomendaciones' => $datos['recomendaciones'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => $datos['estado'] ?? 'realizada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return self::obtenerPorId($id);
    }

    public static function actualizar($id, array $datos)
    {
        $informe = self::obtenerPorIdSimple($id);
        
        if (!$informe) {
            throw new \Exception('Informe no encontrado');
        }

        $datosUpdate = [];
        
        $campos = [
            'inscripcion_id', 'fecha', 'temas_vistos', 'tareas_asignadas',
            'nivel_comprension', 'participacion', 'cumplimiento_tareas',
            'calificacion', 'resumen', 'logros', 'dificultades',
            'recomendaciones', 'observaciones', 'estado'
        ];

        foreach ($campos as $campo) {
            if (isset($datos[$campo])) {
                $datosUpdate[$campo] = $datos[$campo];
            }
        }

        if (!empty($datosUpdate)) {
            $datosUpdate['updated_at'] = now();
            DB::table('informe_clase')->where('id', $id)->update($datosUpdate);
        }

        return self::obtenerPorId($id);
    }

    public static function eliminar($id)
    {
        $informe = self::obtenerPorIdSimple($id);
        
        if (!$informe) {
            throw new \Exception('Informe no encontrado');
        }

        DB::table('informe_clase')->where('id', $id)->delete();
        return true;
    }

    public static function obtenerPorId($id)
    {
        return DB::table('informe_clase')
            ->join('inscripcion', 'informe_clase.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->where('informe_clase.id', $id)
            ->select(
                'informe_clase.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('informe_clase')->where('id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('informe_clase')
            ->join('inscripcion', 'informe_clase.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->select(
                'informe_clase.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre")
            );

        if (isset($filtros['inscripcion_id'])) {
            $query->where('informe_clase.inscripcion_id', $filtros['inscripcion_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('informe_clase.estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('informe_clase.fecha', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('informe_clase.fecha', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('informe_clase.fecha', 'desc')->get();
    }
}