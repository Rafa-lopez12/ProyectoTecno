<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Reprogramacion;
use Illuminate\Http\Request;

class ReprogramacionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'licencia_id' => $request->get('licencia_id'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $reprogramaciones = Reprogramacion::listar($filtros);

            return response()->json([
                'data' => $reprogramaciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar reprogramaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'licencia_id', 'fecha_original', 'fecha_nueva', 'estado', 'observaciones'
            ]);

            $reprogramacion = Reprogramacion::crear($datos);

            return response()->json([
                'message' => 'Reprogramación creada exitosamente',
                'data' => $reprogramacion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $reprogramacion = Reprogramacion::obtenerPorId($id);

            if (!$reprogramacion) {
                return response()->json([
                    'message' => 'Reprogramación no encontrada'
                ], 404);
            }

            return response()->json([
                'data' => $reprogramacion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'fecha_original', 'fecha_nueva', 'estado', 'observaciones'
            ]);

            $reprogramacion = Reprogramacion::actualizar($id, $datos);

            return response()->json([
                'message' => 'Reprogramación actualizada exitosamente',
                'data' => $reprogramacion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Reprogramacion::eliminar($id);

            return response()->json([
                'message' => 'Reprogramación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function marcarRealizada($id)
    {
        try {
            Reprogramacion::marcarRealizada($id);

            return response()->json([
                'message' => 'Reprogramación marcada como realizada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelar($id)
    {
        try {
            Reprogramacion::cancelar($id);

            return response()->json([
                'message' => 'Reprogramación cancelada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar reprogramación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}