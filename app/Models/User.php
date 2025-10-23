<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'fecha_nacimiento',
        'direccion',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'fecha_nacimiento' => 'date',
        'password' => 'hashed',
    ];

    /**
     * Relación con Propietario
     */
    public function propietario()
    {
        return $this->hasOne(Propietario::class);
    }

    /**
     * Relación con Tutor
     */
    public function tutor()
    {
        return $this->hasOne(Tutor::class);
    }

    /**
     * Relación con Alumno
     */
    public function alumno()
    {
        return $this->hasOne(Alumno::class);
    }

    /**
     * Obtener el nombre completo del usuario
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function isActivo()
    {
        return $this->estado === 'activo';
    }
}
