<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'inscripcion_id' => $request->get('inscripcion_id'),
                'tutor_id' => $request->get('tutor_id'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $asistencias = Asistencia::listar($filtros);

            return response()->json([
                'data' => $asistencias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar asistencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'inscripcion_id', 'fecha', 'estado', 'observaciones'
            ]);
            
            $asistencia = Asistencia::crear($datos);

            return response()->json([
                'message' => 'Asistencia registrada exitosamente',
                'data' => $asistencia
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $asistencia = Asistencia::obtenerPorId($id);

            if (!$asistencia) {
                return response()->json([
                    'message' => 'Asistencia no encontrada'
                ], 404);
            }

            return response()->json([
                'data' => $asistencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'fecha', 'estado', 'observaciones'
            ]);

            $asistencia = Asistencia::actualizar($id, $datos);

            return response()->json([
                'message' => 'Asistencia actualizada exitosamente',
                'data' => $asistencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Asistencia::eliminar($id);

            return response()->json([
                'message' => 'Asistencia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function porInscripcion($inscripcionId)
    {
        try {
            $asistencias = Asistencia::obtenerPorInscripcion($inscripcionId);

            return response()->json([
                'data' => $asistencias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener asistencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}