<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use Illuminate\Http\Request;

class LicenciaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'asistencia_id' => $request->get('asistencia_id'),
                'tutor_id' => $request->get('tutor_id'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $licencias = Licencia::listar($filtros);

            return response()->json([
                'data' => $licencias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar licencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'asistencia_id', 'motivo', 'estado'
            ]);

            $licencia = Licencia::crear($datos);

            return response()->json([
                'message' => 'Licencia creada exitosamente',
                'data' => $licencia
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $licencia = Licencia::obtenerPorId($id);

            if (!$licencia) {
                return response()->json([
                    'message' => 'Licencia no encontrada'
                ], 404);
            }

            return response()->json([
                'data' => $licencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Método nuevo: obtener licencia por asistencia
    public function porAsistencia($asistenciaId)
    {
        try {
            $licencia = Licencia::obtenerPorAsistencia($asistenciaId);

            if (!$licencia) {
                return response()->json([
                    'message' => 'No se encontró licencia para esta asistencia'
                ], 404);
            }

            return response()->json([
                'data' => $licencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'motivo', 'estado'
            ]);

            $licencia = Licencia::actualizar($id, $datos);

            return response()->json([
                'message' => 'Licencia actualizada exitosamente',
                'data' => $licencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Licencia::eliminar($id);

            return response()->json([
                'message' => 'Licencia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function aprobar($id)
    {
        try {
            $licencia = Licencia::aprobar($id);

            return response()->json([
                'message' => 'Licencia aprobada exitosamente',
                'data' => $licencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al aprobar licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rechazar($id)
    {
        try {
            $licencia = Licencia::rechazar($id);

            return response()->json([
                'message' => 'Licencia rechazada',
                'data' => $licencia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar licencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reprogramaciones($id)
    {
        try {
            $reprogramaciones = Licencia::obtenerReprogramaciones($id);

            return response()->json([
                'data' => $reprogramaciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener reprogramaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}