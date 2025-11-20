<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Tutor extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tutor';

    protected $fillable = [
        'user_id',
        'email',
        'password',
        'rol',
        'grado',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Validar datos de tutor
     */
    public static function validar(array $datos, $id = null)
    {
        return Validator::make($datos, [
            'email' => 'required|email|unique:tutor,email' . ($id ? ',' . $id : ''),
            'password' => ($id ? 'nullable' : 'required') . '|min:6',
            'rol' => 'nullable|in:tutor,tutor',
            'grado' => 'nullable|string|max:100',
        ]);
    }

    /**
     * Crear tutor con su usuario
     */
    public static function crearConUsuario(array $datosUsuario, array $datosTutor = [])
    {
        $validatorUser = User::validar($datosUsuario);
        if ($validatorUser->fails()) {
            throw new \Exception('Error en datos de usuario: ' . $validatorUser->errors()->first());
        }

        $validatorTutor = self::validar($datosTutor);
        if ($validatorTutor->fails()) {
            throw new \Exception('Error en datos de tutor: ' . $validatorTutor->errors()->first());
        }

        DB::beginTransaction();
        try {
            $userId = User::crear($datosUsuario);

            $tutorId = DB::table('tutor')->insertGetId([
                'user_id' => $userId,
                'rol' => $datosTutor['rol'] ?? 'tutor',
                'grado' => $datosTutor['grado'] ?? null,
                'email' => $datosTutor['email'],
                'password' => Hash::make($datosTutor['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return self::obtenerPorId($tutorId);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar tutor y su usuario
     */
    public static function actualizarConUsuario($id, array $datosUsuario = [], array $datosTutor = [])
    {
        $tutor = self::obtenerPorIdSimple($id);
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            if (!empty($datosUsuario)) {
                User::actualizar($tutor->user_id, $datosUsuario);
            }

            if (!empty($datosTutor)) {
                $datosUpdate = [];
                
                if (isset($datosTutor['rol'])) {
                    $datosUpdate['rol'] = $datosTutor['rol'];
                }
                if (isset($datosTutor['grado'])) {
                    $datosUpdate['grado'] = $datosTutor['grado'];
                }
                if (isset($datosTutor['email'])) {
                    $datosUpdate['email'] = $datosTutor['email'];
                }
                if (isset($datosTutor['password']) && !empty($datosTutor['password'])) {
                    $datosUpdate['password'] = Hash::make($datosTutor['password']);
                }

                if (!empty($datosUpdate)) {
                    $datosUpdate['updated_at'] = now();
                    DB::table('tutor')->where('id', $id)->update($datosUpdate);
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
     * Eliminar tutor (cambiar estado a inactivo)
     */
    public static function eliminarConUsuario($id)
    {
        $tutor = self::obtenerPorIdSimple($id);
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            // Cambiar estado del usuario a inactivo en lugar de eliminar
            DB::table('usuario')
                ->where('id', $tutor->user_id)
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
     * Obtener tutor por ID con datos de usuario
     */
    public static function obtenerPorId($id)
    {
        return DB::table('tutor')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->where('tutor.id', $id)
            ->select(
                'tutor.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
            )
            ->first();
    }

    /**
     * Obtener tutor por ID (solo tabla tutor)
     */
    public static function obtenerPorIdSimple($id)
    {
        return DB::table('tutor')->where('id', $id)->first();
    }

    /**
     * Obtener tutor por email
     */
    public static function obtenerPorEmail($email)
    {
        return DB::table('tutor')->where('email', $email)->first();
    }

    /**
     * Listar tutores (solo activos por defecto)
     */
    public static function listar($filtros = [])
    {
        $query = DB::table('tutor')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->select(
                'tutor.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.fecha_nacimiento',
                'usuario.direccion',
                'usuario.estado'
            );

        // Por defecto solo mostrar activos, a menos que se especifique lo contrario
        if (!isset($filtros['mostrar_inactivos']) || !$filtros['mostrar_inactivos']) {
            $query->where('usuario.estado', 'activo');
        }

        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('usuario.nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('usuario.apellido', 'ILIKE', "%{$search}%")
                  ->orWhere('tutor.email', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filtros['estado'])) {
            $query->where('usuario.estado', $filtros['estado']);
        }

        if (isset($filtros['rol'])) {
            $query->where('tutor.rol', $filtros['rol']);
        }

        return $query->get();
    }
}