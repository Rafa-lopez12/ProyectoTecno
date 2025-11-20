<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'estado' => $request->get('estado'),
                'alumno_id' => $request->get('alumno_id'),
                'tutor_id' => $request->get('tutor_id'),
                'servicio_id' => $request->get('servicio_id'),
            ];

            $inscripciones = Inscripcion::listar($filtros);

            return response()->json([
                'data' => $inscripciones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar inscripciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'id_servicio', 'alumno_id', 'tutor_id',
                'fecha_inscripcion', 'estado', 'observaciones'
            ]);

            $inscripcion = Inscripcion::crear($datos);

            return response()->json([
                'message' => 'Inscripción creada exitosamente',
                'data' => $inscripcion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $inscripcion = Inscripcion::obtenerPorId($id);

            if (!$inscripcion) {
                return response()->json([
                    'message' => 'Inscripción no encontrada'
                ], 404);
            }

            return response()->json([
                'data' => $inscripcion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'id_servicio', 'alumno_id', 'tutor_id',
                'fecha_inscripcion', 'estado', 'observaciones'
            ]);

            $inscripcion = Inscripcion::actualizar($id, $datos);

            return response()->json([
                'message' => 'Inscripción actualizada exitosamente',
                'data' => $inscripcion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Inscripcion::eliminar($id);

            return response()->json([
                'message' => 'Inscripción eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar inscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function informes($id)
    {
        try {
            $informes = Inscripcion::obtenerInformes($id);

            return response()->json([
                'data' => $informes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener informes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}