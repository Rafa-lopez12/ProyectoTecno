<?php
// app/Services/PagoFacilService.php

namespace App\Models\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PagoFacilService
{
    private $baseUrl;
    private $tokenService;
    private $tokenSecret;

    public function __construct()
    {
        $this->baseUrl = env('PAGOFACIL_BASE_URL');
        $this->tokenService = env('PAGOFACIL_TOKEN_SERVICE');
        $this->tokenSecret = env('PAGOFACIL_TOKEN_SECRET');
    }

    /**
     * Autenticar y obtener token
     */
    public function login()
    {
        try {
            $response = Http::withHeaders([
                'tcTokenSecret' => $this->tokenSecret,
                'tcTokenService' => $this->tokenService,
            ])->post($this->baseUrl . '/login');

            $data = $response->json();

            if ($data['error'] === 0 && $data['status'] === 1) {
                $token = $data['values']['accessToken'];
                $expiresIn = $data['values']['expiresInMinutes'];
                
                // Guardar token en cache (expiración en minutos - 5 minutos de margen)
                Cache::put('pagofacil_token', $token, now()->addMinutes($expiresIn - 5));
                
                return [
                    'success' => true,
                    'token' => $token,
                    'expiresIn' => $expiresIn
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Error en autenticación'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al conectar con PagoFácil: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener token (desde cache o renovar)
     */
    private function getToken()
    {
        $token = Cache::get('pagofacil_token');
        
        if (!$token) {
            $result = $this->login();
            return $result['success'] ? $result['token'] : null;
        }
        
        return $token;
    }

    /**
     * Listar métodos de pago habilitados
     */
    public function listEnabledServices()
    {
        try {
            $token = $this->getToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener el token de autenticación'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post($this->baseUrl . '/list-enabled-services');

            $data = $response->json();

            if ($data['error'] === 0) {
                return [
                    'success' => true,
                    'data' => $data['values']
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Error al listar servicios'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar QR para pago
     */
    public function generateQR(array $datos)
    {
        try {
            $token = $this->getToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener el token de autenticación'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post($this->baseUrl . '/generate-qr', $datos);

            $data = $response->json();

            if ($data['error'] === 0 && $data['status'] === 2007) {
                return [
                    'success' => true,
                    'data' => $data['values']
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Error al generar QR'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consultar estado de transacción
     */
    public function queryTransaction($pagofacilTransactionId = null, $companyTransactionId = null)
    {
        try {
            $token = $this->getToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener el token de autenticación'
                ];
            }

            $body = [];
            if ($pagofacilTransactionId) {
                $body['pagofacilTransactionId'] = $pagofacilTransactionId;
            }
            if ($companyTransactionId) {
                $body['companyTransactionId'] = $companyTransactionId;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post($this->baseUrl . '/query-transaction', $body);

            $data = $response->json();

            if ($data['error'] === 0) {
                return [
                    'success' => true,
                    'data' => $data['values']
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Error al consultar transacción'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}