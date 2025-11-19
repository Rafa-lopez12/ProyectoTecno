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

// Alumnos
Route::prefix('alumnos')->group(function () {
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

// Tutores
Route::prefix('tutores')->group(function () {
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