<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Ruta de login (pública) - UNIFICADA
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// Redireccionar root a login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard principal (propietario/tutor)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// ============================================
// RUTAS PARA ALUMNOS
// ============================================
Route::prefix('alumno')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Alumno/Dashboard');
    })->name('alumno.dashboard');
    
    Route::get('/mis-clases', function () {
        return Inertia::render('Alumno/MisClases');
    })->name('alumno.mis-clases');
});

// Usuarios (Alumnos, Tutores y SubPropietarios)
Route::prefix('usuarios')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Usuarios/index');
    })->name('usuarios.index');
});

// Horarios
Route::prefix('horarios')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Horarios/index');
    })->name('horarios.index');
    
    Route::get('/create', function () {
        return Inertia::render('Horarios/Create');
    })->name('horarios.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Horarios/Edit', ['id' => $id]);
    })->name('horarios.edit');
});

// Inscripciones
Route::prefix('inscripciones')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Inscripciones/index');
    })->name('inscripciones.index');
    
    Route::get('/create', function () {
        return Inertia::render('Inscripciones/Create');
    })->name('inscripciones.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Inscripciones/Edit', ['id' => $id]);
    })->name('inscripciones.edit');
});

Route::prefix('inscripciones/{inscripcionId}/informes')->group(function () {
    Route::get('/', function ($inscripcionId) {
        return Inertia::render('Informes/index', ['inscripcionId' => $inscripcionId]);
    })->name('inscripciones.informes.index');
    
    Route::get('/create', function ($inscripcionId) {
        return Inertia::render('Informes/Create', ['inscripcionId' => $inscripcionId]);
    })->name('inscripciones.informes.create');
    
    Route::get('/{id}', function ($inscripcionId, $id) {
        return Inertia::render('Informes/Show', ['inscripcionId' => $inscripcionId, 'id' => $id]);
    })->name('inscripciones.informes.show');
    
    Route::get('/{id}/edit', function ($inscripcionId, $id) {
        return Inertia::render('Informes/Edit', ['inscripcionId' => $inscripcionId, 'id' => $id]);
    })->name('inscripciones.informes.edit');
});

// Asistencias
Route::prefix('asistencias')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Asistencias/index');
    })->name('asistencias.index');
    
    Route::get('/inscripcion/{inscripcionId}', function ($inscripcionId) {
        return Inertia::render('Asistencias/PorInscripcion', ['inscripcionId' => $inscripcionId]);
    })->name('asistencias.por-inscripcion');
    
    Route::get('/{asistenciaId}', function ($asistenciaId) {
        return Inertia::render('Asistencias/Detalle', ['asistenciaId' => $asistenciaId]);
    })->name('asistencias.detalle');
    
    Route::get('/licencia/{licenciaId}/reprogramacion', function ($licenciaId) {
        return Inertia::render('Asistencias/Reprogramacion', ['licenciaId' => $licenciaId]);
    })->name('asistencias.reprogramacion');
});

// Ventas
Route::prefix('ventas')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Ventas/Index');
    })->name('ventas.index');
    
    Route::get('/create', function () {
        return Inertia::render('Ventas/Create');
    })->name('ventas.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Ventas/Edit', ['id' => $id]);
    })->name('ventas.edit');
});

// Propietarios
Route::prefix('propietarios')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Propietarios/Index');
    })->name('propietarios.index');
    
    Route::get('/create', function () {
        return Inertia::render('Propietarios/Create');
    })->name('propietarios.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Propietarios/Edit', ['id' => $id]);
    })->name('propietarios.edit');
});