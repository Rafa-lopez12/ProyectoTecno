<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumnoController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\Api\PropietarioController;
use App\Http\Controllers\Api\HorarioController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\InformeClaseController;
use App\Models\Servicio;

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

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
        Route::get('inscripcion/{id}/informes', [InscripcionController::class, 'informes']);
        
        // Informes de Clase
        Route::apiResource('informes-clase', InformeClaseController::class);
        
        // Servicios
        Route::get('servicios', function () {
            return response()->json([
                'message' => 'Lista de servicios',
                'data' => Servicio::listarActivos()
            ]);
        });
    });
});