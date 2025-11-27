<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Propietario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'propietario';

    protected $fillable = [
        'user_id',
        'email',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Validar datos de propietario
     */
    public static function validar(array $datos, $id = null)
    {
        $reglas = [
            'email' => 'required|email|unique:propietario,email' . ($id ? ',' . $id : ''),
            'password' => ($id ? 'nullable' : 'required') . '|min:8',
            'rol' => 'nullable|string',
        ];

        return Validator::make($datos, $reglas);
    }

    /**
     * Crear propietario con su usuario
     */
    public static function crearConUsuario(array $datosUsuario, array $datosPropietario)
    {
        // Validar datos de usuario
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        // Validar datos de propietario
        $validatorPropietario = self::validar($datosPropietario);
        if ($validatorPropietario->fails()) {
            throw new \Exception('Error en datos de propietario: ' . $validatorPropietario->errors()->first());
        }

        DB::beginTransaction();
        try {
            // Crear usuario
            $userId = User::crear($datosUsuario);

            // Crear propietario
            $propietarioId = DB::table('propietario')->insertGetId([
                'user_id' => $userId,
                'email' => $datosPropietario['email'],
                'password' => Hash::make($datosPropietario['password']),
                'rol' => $datosPropietario['rol'] ?? 'subPropietario',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return self::obtenerPorId($propietarioId);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar propietario y su usuario
     */
    public static function actualizarConUsuario($id, array $datosUsuario = [], array $datosPropietario = [])
    {
        $propietario = self::obtenerPorId($id);
        
        if (!$propietario) {
            throw new \Exception('Propietario no encontrado');
        }

        DB::beginTransaction();
        try {
            // Actualizar usuario si hay datos
            if (!empty($datosUsuario)) {
                User::actualizar($propietario->user_id, $datosUsuario);
            }

            // Actualizar propietario si hay datos
            if (!empty($datosPropietario)) {
                $datosUpdate = [];
                
                if (isset($datosPropietario['email'])) {
                    $datosUpdate['email'] = $datosPropietario['email'];
                }
                if (isset($datosPropietario['password'])) {
                    $datosUpdate['password'] = Hash::make($datosPropietario['password']);
                }
                if (isset($datosPropietario['rol'])) {
                    $datosUpdate['rol'] = $datosPropietario['rol'];
                }

                if (!empty($datosUpdate)) {
                    $datosUpdate['updated_at'] = now();
                    DB::table('propietario')->where('id', $id)->update($datosUpdate);
                }
            }

            DB::commit();

            return self::obtenerPorId($id);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar propietario (cambiar estado a inactivo)
     */
    public static function eliminarConUsuario($id)
    {
        $propietario = self::obtenerPorId($id);
        
        if (!$propietario) {
            throw new \Exception('Propietario no encontrado');
        }

        DB::beginTransaction();
        try {
            // Cambiar estado del usuario a inactivo en lugar de eliminar
            DB::table('usuario')
                ->where('id', $propietario->user_id)
                ->update([
                    'estado' => 'inactivo',
                    'updated_at' => now()
                ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener propietario por ID con datos de usuario
     */
    public static function obtenerPorId($id)
    {
        return DB::table('propietario')
            ->join('usuario', 'propietario.user_id', '=', 'usuario.id')
            ->where('propietario.id', $id)
            ->select(
                'propietario.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
            )
            ->first();
    }

    public static function obtenerPorEmail($email)
    {
        return DB::table('propietario')->where('email', $email)->first();
    }

    /**
     * Validar credenciales de login
     */
    public static function validarCredenciales($email, $password)
    {
        $propietario = self::obtenerPorEmail($email);

        if (!$propietario || !Hash::check($password, $propietario->password)) {
            return null;
        }

        return $propietario;
    }

    /**
     * Listar propietarios (solo activos por defecto)
     */
    public static function listar($filtros = [])
    {
        $query = DB::table('propietario')
            ->join('usuario', 'propietario.user_id', '=', 'usuario.id')
            ->select(
                'propietario.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.estado'
            );

        // Por defecto solo mostrar activos, a menos que se especifique lo contrario
        if (!isset($filtros['mostrar_inactivos']) || !$filtros['mostrar_inactivos']) {
            $query->where('usuario.estado', 'activo');
        }

        // Aplicar filtros
        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('usuario.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('usuario.apellido', 'ILIKE', "%{$search}%")
                  ->orWhere('propietario.email', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['estado'])) {
            $query->where('usuario.estado', $filtros['estado']);
        }

        return $query->get();
    }


}