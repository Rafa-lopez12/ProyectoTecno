<?php
// app/Http/Controllers/Api/VentaController.php - Agregar método para alumnos

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function misVentas(Request $request)
    {
        try {
            $user = $request->user();
            
            // Verificar que sea alumno
            if (get_class($user) !== Alumno::class) {
                return response()->json([
                    'message' => 'No autorizado'
                ], 403);
            }

            // Obtener ventas del alumno
            $ventas = DB::table('venta')
                ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
                ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
                ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
                ->join('servicio', 'inscripcion.id_servicio', '=', 'servicio.id')
                ->where('alumno.id', $user->id)
                ->select(
                    'venta.*',
                    DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre"),
                    'alumno.ci as alumno_ci',
                    'servicio.nombre as servicio_nombre'
                )
                ->orderBy('venta.fecha_venta', 'desc')
                ->get();

            return response()->json(['data' => $ventas]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener ventas',
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

    public function show($id)
    {
        try {
            $venta = DB::table('venta')
                ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
                ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
                ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
                ->where('venta.id', $id)
                ->select(
                    'venta.*',
                    'alumno.ci as alumno_ci',
                    DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre")
                )
                ->first();
    
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data' => $venta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}