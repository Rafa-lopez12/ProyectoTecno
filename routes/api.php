<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumnoController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\Api\PropietarioController;

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
    });
});