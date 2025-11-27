<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumnoController;
use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\Api\PropietarioController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\InformeClaseController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\ReprogramacionController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\PagoController;
use App\Models\Servicio;

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); // Login unificado
});

// Callback público de PagoFácil
Route::post('v1/pago/callback', [PagoController::class, 'callback']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // API v1
    Route::prefix('v1')->group(function () {
        // Alumnos
        Route::apiResource('alumnos', AlumnoController::class);
        
        // Tutores
        Route::apiResource('tutores', TutorController::class);
        
        // Propietarios
        Route::apiResource('propietarios', PropietarioController::class);
        
        // Horarios
        Route::apiResource('horario', HorarioController::class);
        Route::post('horario/{id}/asignar-tutor', [HorarioController::class, 'asignarTutor']);
        Route::post('horario/{id}/desasignar-tutor', [HorarioController::class, 'desasignarTutor']);
        Route::get('horario/{id}/tutores', [HorarioController::class, 'tutoresDeHorario']);
        Route::get('tutores/{tutorId}/horario', [HorarioController::class, 'horariosDeTutor']);
        
        // Inscripciones
        Route::apiResource('inscripcion', InscripcionController::class);
        Route::post('inscripcion/{id}/cambiar-estado', [InscripcionController::class, 'cambiarEstado']);
        Route::get('inscripcion/{id}/informes', [InscripcionController::class, 'informes']);
        
        // Informes de Clase
        Route::apiResource('informe-clase', InformeClaseController::class);
        Route::get('informe-clase/asistencia/{asistenciaId}', [InformeClaseController::class, 'porAsistencia']);
        
        // Servicios
        Route::get('servicios', function () {
            return response()->json([
                'message' => 'Lista de servicios',
                'data' => Servicio::listarActivos()
            ]);
        });

        // Asistencias
        Route::apiResource('asistencia', AsistenciaController::class);
        Route::get('asistencia/inscripcion/{inscripcionId}', [AsistenciaController::class, 'porInscripcion']);
        
        // Licencias
        Route::apiResource('licencia', LicenciaController::class);
        Route::get('licencia/asistencia/{asistenciaId}', [LicenciaController::class, 'porAsistencia']); 
        Route::post('licencia/{id}/aprobar', [LicenciaController::class, 'aprobar']);
        Route::post('licencia/{id}/rechazar', [LicenciaController::class, 'rechazar']);
        Route::get('licencia/{id}/reprogramacione', [LicenciaController::class, 'reprogramaciones']);
        
        // Reprogramaciones
        Route::apiResource('reprogramacione', ReprogramacionController::class);
        Route::post('reprogramacione/{id}/marcar-realizada', [ReprogramacionController::class, 'marcarRealizada']);
        Route::post('reprogramacione/{id}/cancelar', [ReprogramacionController::class, 'cancelar']);

        // Ventas
        Route::get('venta', [VentaController::class, 'index']);
        Route::get('venta/reporte-mensual', [VentaController::class, 'reporteMensual']);
        Route::get('venta/reporte-estado', [VentaController::class, 'reportePorEstado']);
        Route::get('venta/mis-ventas', [VentaController::class, 'misVentas']); // Para alumnos
        Route::get('venta/{id}', [VentaController::class, 'show']);
        
        // Pagos
        Route::apiResource('pago', PagoController::class);
        Route::post('pago/generar-qr', [PagoController::class, 'generarQR']);
      
        Route::get('pago/{id}/consultar-estado', [PagoController::class, 'consultarEstado']);
        Route::get('pago/venta/{ventaId}', [PagoController::class, 'porVenta']);
        Route::get('pago/metodos-habilitados', [PagoController::class, 'metodosHabilitados']);
        
    });
});