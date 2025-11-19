<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// Dashboard con ejemplo de autenticación
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Rutas de Alumnos
Route::middleware(['auth'])->prefix('alumnos')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Alumnos/Index');
    })->name('alumnos.index');
    
    Route::get('/create', function () {
        return Inertia::render('Alumnos/Create');
    })->name('alumnos.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Alumnos/Edit', ['id' => $id]);
    })->name('alumnos.edit');
});

// Rutas de Tutores
Route::middleware(['auth'])->prefix('tutores')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Tutores/Index');
    })->name('tutores.index');
    
    Route::get('/create', function () {
        return Inertia::render('Tutores/Create');
    })->name('tutores.create');
    
    Route::get('/{id}/edit', function ($id) {
        return Inertia::render('Tutores/Edit', ['id' => $id]);
    })->name('tutores.edit');
});

// Rutas de Propietarios
Route::middleware(['auth'])->prefix('propietarios')->group(function () {
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