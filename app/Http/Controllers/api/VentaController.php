<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filtros = [
                'estado' => $request->get('estado'),
                'tipo_venta' => $request->get('tipo_venta'),
                'inscripcion_id' => $request->get('inscripcion_id'),
            ];

            $ventas = Venta::listar($filtros);

            return response()->json(['data' => $ventas]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reporteMensual(Request $request)
    {
        try {
            $mes = $request->get('mes', date('m'));
            $anio = $request->get('anio', date('Y'));

            $reporte = Venta::reporteMensual($mes, $anio);

            return response()->json(['data' => $reporte]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reportePorEstado()
    {
        try {
            $reporte = Venta::reportePorEstado();

            return response()->json(['data' => $reporte]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}