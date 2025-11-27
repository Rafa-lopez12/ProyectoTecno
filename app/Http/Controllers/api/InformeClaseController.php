<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InformeClase;
use Illuminate\Http\Request;

class InformeClaseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'asistencia_id' => $request->get('asistencia_id'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $informes = InformeClase::listar($filtros);

            return response()->json([
                'data' => $informes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar informes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'asistencia_id', 'fecha', 'temas_vistos', 'tareas_asignadas',
                'nivel_comprension', 'participacion', 'cumplimiento_tareas',
                'calificacion', 'resumen', 'logros', 'dificultades',
                'recomendaciones', 'observaciones', 'estado'
            ]);

            $informe = InformeClase::crear($datos);

            return response()->json([
                'message' => 'Informe creado exitosamente',
                'data' => $informe
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear informe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $informe = InformeClase::obtenerPorId($id);

            if (!$informe) {
                return response()->json([
                    'message' => 'Informe no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $informe
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener informe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'inscripcion_id', 'fecha', 'temas_vistos', 'tareas_asignadas',
                'nivel_comprension', 'participacion', 'cumplimiento_tareas',
                'calificacion', 'resumen', 'logros', 'dificultades',
                'recomendaciones', 'observaciones', 'estado'
            ]);

            $informe = InformeClase::actualizar($id, $datos);

            return response()->json([
                'message' => 'Informe actualizado exitosamente',
                'data' => $informe
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar informe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            InformeClase::eliminar($id);

            return response()->json([
                'message' => 'Informe eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar informe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function porAsistencia($asistenciaId)
{
    try {
        $informe = InformeClase::obtenerPorAsistencia($asistenciaId);

        return response()->json([
            'data' => $informe
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al obtener informe',
            'error' => $e->getMessage()
        ], 500);
    }
}
}