<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    /**
     * Listar todos los alumnos
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'search' => $request->get('search'),
                'grado_escolar' => $request->get('grado_escolar'),
                'estado' => $request->get('estado'),
            ];

            $alumnos = Alumno::listar($filtros);

            return response()->json([
                'data' => $alumnos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar alumnos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo alumno
     */
    public function store(Request $request)
    {
        try {
            $datosUsuario = $request->only([
                'nombre', 'apellido', 'telefono', 
                'fecha_nacimiento', 'direccion', 'estado'
            ]);

            $datosAlumno = $request->only([
                'grado_escolar', 'fecha_ingreso'
            ]);

            $alumno = Alumno::crearConUsuario($datosUsuario, $datosAlumno);

            return response()->json([
                'message' => 'Alumno creado exitosamente',
                'data' => $alumno
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un alumno específico
     */
    public function show($id)
    {
        try {
            $alumno = Alumno::obtenerPorId($id);

            if (!$alumno) {
                return response()->json([
                    'message' => 'Alumno no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $alumno
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un alumno
     */
    public function update(Request $request, $id)
    {
        try {
            $datosUsuario = $request->only([
                'nombre', 'apellido', 'telefono', 
                'fecha_nacimiento', 'direccion', 'estado'
            ]);

            $datosAlumno = $request->only([
                'grado_escolar'
            ]);

            $alumno = Alumno::actualizarConUsuario($id, $datosUsuario, $datosAlumno);

            return response()->json([
                'message' => 'Alumno actualizado exitosamente',
                'data' => $alumno
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un alumno
     */
    public function destroy($id)
    {
        try {
            Alumno::eliminarConUsuario($id);

            return response()->json([
                'message' => 'Alumno eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}