<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Ruta de login (pública)
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// Redireccionar root a login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

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

// Asistencias
Route::prefix('asistencias')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Asistencias/Index');
    })->name('asistencias.index');
    
    Route::get('/create', function () {
        return Inertia::render('Asistencias/Create');
    })->name('asistencias.create');
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