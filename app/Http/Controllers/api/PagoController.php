<?php
// app/Http/Controllers/Api/PagoController.php - Actualizar

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Service\PagoFacilService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    private $pagoFacilService;

    public function __construct(PagoFacilService $pagoFacilService)
    {
        $this->pagoFacilService = $pagoFacilService;
    }

    public function index(Request $request)
    {
        try {
            $filtros = [
                'venta_id' => $request->get('venta_id'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
            ];

            $pagos = Pago::listar($filtros);

            return response()->json([
                'data' => $pagos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $datos = $request->only([
                'venta_id', 'monto', 'metodo_pago', 
                'observaciones', 'registrado_por'
            ]);

            $pago = Pago::crear($datos);

            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'data' => $pago
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generarQR(Request $request)
    {
        try {
            // Solo necesitamos venta_id, monto y email opcional
            $datos = $request->only([
                'venta_id', 
                'monto', 
                'email'
            ]);

            $result = Pago::generarQR($datos);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'message' => 'QR generado exitosamente',
                'data' => $result['data']
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $pago = Pago::obtenerPorId($id);

            if (!$pago) {
                return response()->json([
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $pago
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarEstado(Request $request, $id)
    {
        try {
            $pago = Pago::obtenerPorIdSimple($id);

            if (!$pago) {
                return response()->json([
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            $result = $this->pagoFacilService->queryTransaction(
                $pago->pagofacil_transaction_id,
                $pago->company_transaction_id
            );

            if ($result['success']) {
                // Verificar si el pago fue completado
                $paymentStatus = $result['data']['paymentStatus'] ?? null;
                
                if ($paymentStatus === 2) { // 2 = Pagado en PagoFácil
                    Pago::actualizarEstadoPago($pago->company_transaction_id, 'Completado');
                }

                return response()->json([
                    'message' => 'Estado consultado exitosamente',
                    'data' => $result['data'],
                    'pago_completado' => $paymentStatus === 2
                ]);
            }

            return response()->json([
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al consultar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function porVenta($ventaId)
    {
        try {
            $pagos = DB::table('pago')
                ->leftJoin('propietario', 'pago.registrado_por', '=', 'propietario.id')
                ->leftJoin('usuario', 'propietario.user_id', '=', 'usuario.id')
                ->where('pago.venta_id', $ventaId)
                ->select(
                    'pago.*',
                    DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as registrado_por_nombre")
                )
                ->orderBy('pago.created_at', 'desc')
                ->get();
    
            return response()->json([
                'success' => true,
                'data' => $pagos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    private function actualizarSaldosVenta($ventaId)
    {
        try {
          
            $totalPagado = DB::table('pago')
                ->where('venta_id', $ventaId)
                ->where('estado', 'pagado') // Solo contar pagos pagados
                ->sum('monto');

            $venta = DB::table('venta')->where('id', $ventaId)->first();

            if (!$venta) {
                return;
            }

            $montoTotal = floatval($venta->monto_total);
            $saldoPendiente = $montoTotal - floatval($totalPagado);

            $nuevoEstado = 'pendiente';
            if ($saldoPendiente <= 0) {
                $nuevoEstado = 'pagado';
                $saldoPendiente = 0;
            } elseif ($totalPagado > 0) {
                $nuevoEstado = 'parcial';
            }

            DB::table('venta')
                ->where('id', $ventaId)
                ->update([
                    'monto_pagado' => $totalPagado,
                    'saldo_pendiente' => $saldoPendiente,
                    'estado' => $nuevoEstado,
                    'updated_at' => now()
                ]);

            

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar saldos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function callback(Request $request)
{
    try {
        Log::info('========== INICIO CALLBACK PAGOFACIL ==========');
        Log::info('Body recibido:', $request->all());
        $validator = Validator::make($request->all(), [
            'PedidoID' => 'required|string',
            'Fecha' => 'required|string',
            'Hora' => 'required|string',
            'MetodoPago' => 'required|integer',
            'Estado' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 1,
                'status' => 0,
                'message' => 'Datos inválidos',
                'values' => false
            ], 400);
        }

        $pedidoId = $request->input('PedidoID');
        $estado = $request->input('Estado');
        $metodoPago = $request->input('MetodoPago');
        $fecha = $request->input('Fecha');
        $hora = $request->input('Hora');

        $pago = DB::table('pago')
            ->where('company_transaction_id', $pedidoId)
            ->first();

        if (!$pago) {
            Log::warning("Pago no encontrado para PedidoID: {$pedidoId}");
            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => 'Pago no encontrado pero notificación recibida',
                'values' => true
            ], 200);
        }

        if ($estado === 2) {
         
            DB::table('pago')
                ->where('id', $pago->id)
                ->update([
                    'estado' => 'pagado',
                    'metodo_pago' => 'QR',
                    'observaciones' => "Pago completado vía {$metodoPago} el {$fecha} a las {$hora}",
                    'updated_at' => now()
                ]);

            $this->actualizarSaldosVenta($pago->venta_id);
            Log::info("Pago actualizado exitosamente: ID={$pago->id}, Estado=pagado");

            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => 'Pago confirmado y actualizado exitosamente',
                'values' => true
            ], 200);
        } else {
            Log::info("ALGO SALIO RELATIVAMENTE MAL");
            return response()->json([
                'error' => 0,
                'status' => 1,
                'message' => "Notificación recibida. Estado: {$estado}",
                'values' => true
            ], 200);
        }

    } catch (\Exception $e) {
        Log::error('Error en callback:', [
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
        return response()->json([
            'message' => 'Error en el callback',
            'error' => $e->getMessage()
        ], 500);
    }
}

    
}