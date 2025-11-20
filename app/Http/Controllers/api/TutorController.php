<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    /**
     * Listar todos los tutores
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'search' => $request->get('search'),
                'estado' => $request->get('estado'),
            ];

            $tutores = Tutor::listar($filtros);

            return response()->json([
                'data' => $tutores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar tutores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo tutor
     */
    public function store(Request $request)
    {
        try {
            // Datos de usuario (tabla usuario)
            $datosUsuario = $request->only([
                'nombre', 'apellido', 'telefono', 
                'fecha_nacimiento', 'direccion', 'estado'
            ]);

            // Datos de tutor (tabla tutor)
            $datosTutor = $request->only([
                'email', 'password', 'grado', 'rol'
            ]);

            $tutor = Tutor::crearConUsuario($datosUsuario, $datosTutor);

            return response()->json([
                'message' => 'Tutor creado exitosamente',
                'data' => $tutor
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un tutor específico
     */
    public function show($id)
    {
        try {
            $tutor = Tutor::obtenerPorId($id);

            if (!$tutor) {
                return response()->json([
                    'message' => 'Tutor no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $tutor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un tutor
     */
    public function update(Request $request, $id)
    {
        try {
            // Datos de usuario
            $datosUsuario = $request->only([
                'nombre', 'apellido', 'telefono', 
                'fecha_nacimiento', 'direccion', 'estado'
            ]);

            // Datos de tutor
            $datosTutor = $request->only([
                'email', 'password', 'grado', 'rol'
            ]);

            $tutor = Tutor::actualizarConUsuario($id, $datosUsuario, $datosTutor);

            return response()->json([
                'message' => 'Tutor actualizado exitosamente',
                'data' => $tutor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un tutor
     */
    public function destroy($id)
    {
        try {
            Tutor::eliminarConUsuario($id);

            return response()->json([
                'message' => 'Tutor eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar tutor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}