<?php
// app/Http/Controllers/Api/InscripcionController.php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\Tutor;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $filtros = [
                'estado' => $request->get('estado'),
                'alumno_id' => $request->get('alumno_id'),
                'servicio_id' => $request->get('servicio_id'),
            ];
    
            // Si es tutor, solo ver sus inscripciones
            if (get_class($user) === Tutor::class) {
                $filtros['tutor_id'] = $user->id;
            }
    
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
                'id_servicio', 'alumno_id', 'tutor_id', 'horarios',
                'fecha_inscripcion', 'estado', 'observaciones',
                'crear_venta', 'propietario_id', 'tipo_venta',
                'monto_total', 'monto_pagado', 'mes_correspondiente',
                'fecha_venta', 'fecha_vencimiento'
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
                'id_servicio', 'alumno_id', 'tutor_id', 'horarios',
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

    public function cambiarEstado(Request $request, $id)
    {
        try {
            $nuevoEstado = $request->input('estado');

            if (!$nuevoEstado) {
                return response()->json([
                    'message' => 'El estado es requerido'
                ], 422);
            }

            $inscripcion = Inscripcion::cambiarEstado($id, $nuevoEstado);

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'data' => $inscripcion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar estado',
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

}