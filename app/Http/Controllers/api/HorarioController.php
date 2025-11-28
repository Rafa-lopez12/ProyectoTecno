<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    /**
     * Listar todos los horarios
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'search' => $request->get('search'),
                'dia_semana' => $request->get('dia_semana'),
                'estado' => $request->get('estado'),
            ];

            $horarios = Horario::listar($filtros);

            return response()->json([
                'data' => $horarios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar horarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo horario
     */
    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'dia_semana',
                'hora_inicio',
                'hora_fin',
                'estado'
            ]);

            $horario = Horario::crear($datos);

            return response()->json([
                'message' => 'Horario creado exitosamente',
                'data' => $horario
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un horario específico
     */
    public function show($id)
    {
        try {
            $horario = Horario::obtenerPorId($id);

            if (!$horario) {
                return response()->json([
                    'message' => 'Horario no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $horario
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un horario
     */
    public function update(Request $request, $id)
    {
        try {
            $datos = $request->only([
                'dia_semana',
                'hora_inicio',
                'hora_fin',
                'estado'
            ]);

            $horario = Horario::actualizar($id, $datos);

            return response()->json([
                'message' => 'Horario actualizado exitosamente',
                'data' => $horario
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un horario
     */
    public function destroy($id)
    {
        try {
            Horario::eliminar($id);

            return response()->json([
                'message' => 'Horario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar horario a tutor
     */
    public function asignarTutor(Request $request, $id)
    {
        try {
            $tutorId = $request->input('tutor_id');

            if (!$tutorId) {
                return response()->json([
                    'message' => 'El ID del tutor es requerido'
                ], 422);
            }

            Horario::asignarATutor($id, $tutorId);

            return response()->json([
                'message' => 'Horario asignado al tutor exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desasignar horario de tutor
     */
    public function desasignarTutor(Request $request, $id)
    {
        try {
            $tutorId = $request->input('tutor_id');

            if (!$tutorId) {
                return response()->json([
                    'message' => 'El ID del tutor es requerido'
                ], 422);
            }

            Horario::desasignarDeTutor($id, $tutorId);

            return response()->json([
                'message' => 'Horario desasignado del tutor exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al desasignar horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horarios de un tutor
     */
    public function horariosDeTutor($tutorId)
    {
        try {
            $horarios = Horario::obtenerHorariosDeTutor($tutorId);

            return response()->json([
                'data' => $horarios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener horarios del tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tutores de un horario
     */
    public function tutoresDeHorario($id)
    {
        try {
            $tutores = Horario::obtenerTutoresDeHorario($id);

            return response()->json([
                'data' => $tutores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener tutores del horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}