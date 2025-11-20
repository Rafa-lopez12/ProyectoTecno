<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'servicio';

    protected $fillable = [
        'nombre',
        'descripcion',
        'requiere_direccion',
        'requiere_foto',
        'estado'
    ];

    protected $casts = [
        'requiere_direccion' => 'boolean',
        'requiere_foto' => 'boolean',
        'estado' => 'boolean'
    ];

    // Método para listar servicios activos
    public static function listarActivos()
    {
        return self::where('estado', true)->get();
    }

    // Relaciones
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'id_servicio');
    }
}