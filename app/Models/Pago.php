<?php
// app/Models/Pago.php - Actualizar método generarQR

namespace App\Models;

use App\Models\Service\PagoFacilService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class Pago
{
    /**
     * Generar código único para la transacción
     */
    private static function generarCodigoTransaccion()
    {
        do {
            // Generar código: grupo16sa- + 5 dígitos aleatorios
            $codigo = 'grupo16sa-' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            
            // Verificar que no exista
            $existe = DB::table('pago')->where('company_transaction_id', $codigo)->exists();
        } while ($existe);

        return $codigo;
    }

    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'venta_id' => 'required|exists:venta,id',
            'monto' => 'required|numeric|min:0.1',
            'metodo_pago' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'registrado_por' => 'nullable|exists:propietario,id'
        ]);
    }

    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        if ($validator->fails()) {
            throw new \Exception('Error en datos: ' . $validator->errors()->first());
        }

        $id = DB::table('pago')->insertGetId([
            'venta_id' => $datos['venta_id'],
            'monto' => $datos['monto'],
            'fecha_pago' => $datos['fecha_pago'] ?? now(),
            'metodo_pago' => $datos['metodo_pago'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => 'pagado',
            'registrado_por' => $datos['registrado_por'] ?? null,
            'pagofacil_transaction_id' => $datos['pagofacil_transaction_id'] ?? null,
            'company_transaction_id' => $datos['company_transaction_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Actualizar saldo de venta
        if (isset($datos['venta_id'])) {
            self::actualizarSaldoVenta($datos['venta_id']);
        }

        return self::obtenerPorId($id);
    }

    public static function generarQR(array $datos)
    {
        DB::beginTransaction();
        try {
            $pagoFacilService = new PagoFacilService();
            
            // Obtener datos de la venta y alumno
            $venta = DB::table('venta')
                ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
                ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
                ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
                ->join('servicio', 'inscripcion.id_servicio', '=', 'servicio.id')
                ->where('venta.id', $datos['venta_id'])
                ->select(
                    'venta.*',
                    'alumno.ci as alumno_ci',
                    'alumno.codigo as alumno_codigo',
                    'usuario.nombre as alumno_nombre',
                    'usuario.apellido as alumno_apellido',
                    'usuario.telefono as alumno_telefono',
                    'servicio.nombre as servicio_nombre'
                )
                ->first();

            if (!$venta) {
                throw new \Exception('Venta no encontrada');
            }

            // Generar código único para la transacción
            $companyTransactionId = self::generarCodigoTransaccion();
            

            // Preparar datos para PagoFácil
            $qrData = [
                'paymentMethod' => 4, // Siempre 4
                'clientName' => $venta->alumno_nombre . ' ' . $venta->alumno_apellido,
                'documentType' => 1, // Siempre 1 (CI)
                'documentId' => $venta->alumno_ci,
                'phoneNumber' => $venta->alumno_telefono ?? '',
                'email' => $datos['email'] ?? '',
                'paymentNumber' => $companyTransactionId,
                'amount' => floatval($datos['monto']),
                'currency' => 2, // Siempre 2 (BOB)
                'clientCode' => $venta->alumno_codigo,
                'callbackUrl' => 'https://f269eb9685df.ngrok-free.app/api/v1/pago/callback',
                'orderDetail' => [
                    [
                        'serial' => 1,
                        'product' => $venta ? $venta->servicio_nombre : 'Servicio_inscripcion',
                        'quantity' => 1,
                        'price' => floatval($datos['monto']),
                        'discount' => 0,
                        'total' => floatval($datos['monto'])
                    ]
                ]
            ];

            // Generar QR en PagoFácil
            $result = $pagoFacilService->generateQR($qrData);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Crear registro de pago
            $pagoId = DB::table('pago')->insertGetId([
                'venta_id' => $datos['venta_id'],
                'monto' => $datos['monto'],
                'fecha_pago' => now(),
                'metodo_pago' => 'QR PagoFácil',
                'estado' => 'pendiente',
                'observaciones' => $datos['observaciones'] ?? null,
                'registrado_por' => $datos['registrado_por'] ?? null,
                'pagofacil_transaction_id' => $result['data']['transactionId'], 
                'company_transaction_id' => $companyTransactionId, 
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => array_merge(
                    (array) self::obtenerPorId($pagoId),
                    $result['data']
                )
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public static function actualizarEstadoPago($companyTransactionId, $estado)
    {
        $pago = DB::table('pago')
            ->where('company_transaction_id', $companyTransactionId)
            ->first();

        if (!$pago) {
            throw new \Exception('Pago no encontrado');
        }

        // Actualizar estado del pago si fue completado
        if ($estado === 'Completado') {
            DB::table('pago')
                ->where('id', $pago->id)
                ->update([
                    'metodo_pago' => 'QR PagoFácil - Completado',
                    'updated_at' => now()
                ]);

            // Actualizar saldo de la venta
            self::actualizarSaldoVenta($pago->venta_id);
        }

        return self::obtenerPorId($pago->id);
    }

    private static function actualizarSaldoVenta($ventaId)
    {
        // Calcular total pagado (solo pagos con método completado)
        $totalPagado = DB::table('pago')
            ->where('venta_id', $ventaId)
            ->where('metodo_pago', 'like', '%Completado%')
            ->sum('monto');

        $venta = DB::table('venta')->where('id', $ventaId)->first();

        if ($venta) {
            $saldoPendiente = $venta->monto_total - $totalPagado;
            $estado = $saldoPendiente <= 0 ? 'pagado' : 'pendiente';

            DB::table('venta')
                ->where('id', $ventaId)
                ->update([
                    'monto_pagado' => $totalPagado,
                    'saldo_pendiente' => max(0, $saldoPendiente),
                    'estado' => $estado,
                    'updated_at' => now()
                ]);
        }
    }

    public static function obtenerPorId($id)
    {
        return DB::table('pago')
            ->leftJoin('venta', 'pago.venta_id', '=', 'venta.id')
            ->leftJoin('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
            ->leftJoin('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->leftJoin('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->leftJoin('propietario', 'pago.registrado_por', '=', 'propietario.id')
            ->leftJoin('usuario as u_prop', 'propietario.user_id', '=', 'u_prop.id')
            ->where('pago.id', $id)
            ->select(
                'pago.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre"),
                'alumno.ci as alumno_ci',
                'alumno.codigo as alumno_codigo',
                DB::raw("CONCAT(u_prop.nombre, ' ', u_prop.apellido) as registrado_por_nombre")
            )
            ->first();
    }

    public static function obtenerPorIdSimple($id)
    {
        return DB::table('pago')->where('pagofacil_transaction_id', $id)->first();
    }

    public static function listar($filtros = [])
    {
        $query = DB::table('pago')
            ->join('venta', 'pago.venta_id', '=', 'venta.id')
            ->join('inscripcion', 'venta.inscripcion_id', '=', 'inscripcion.id')
            ->join('alumno', 'inscripcion.alumno_id', '=', 'alumno.id')
            ->join('usuario', 'alumno.user_id', '=', 'usuario.id')
            ->select(
                'pago.*',
                DB::raw("CONCAT(usuario.nombre, ' ', usuario.apellido) as alumno_nombre"),
                'alumno.ci as alumno_ci',
                'alumno.codigo as alumno_codigo',
                'venta.mes_correspondiente'
            );

        if (isset($filtros['venta_id'])) {
            $query->where('pago.venta_id', $filtros['venta_id']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('pago.fecha_pago', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('pago.fecha_pago', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('pago.fecha_pago', 'desc')->get();
    }

    public static function obtenerPorVenta($ventaId)
    {
        return self::listar(['venta_id' => $ventaId]);
    }
}