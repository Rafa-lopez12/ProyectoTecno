<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PropietarioController extends Controller
{
    /**
     * Listar todos los propietarios
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('propietario')
                ->join('usuario', 'propietario.user_id', '=', 'usuario.id')
                ->select(
                    'propietario.*',
                    'usuario.nombre',
                    'usuario.apellido',
                    'usuario.telefono',
                    'usuario.fecha_nacimiento',
                    'usuario.direccion',
                    'usuario.estado'
                );

            // Filtro por búsqueda
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('usuario.nombre', 'ILIKE', "%{$search}%")
                      ->orWhere('usuario.apellido', 'ILIKE', "%{$search}%")
                      ->orWhere('propietario.email', 'ILIKE', "%{$search}%");
                });
            }

            // Filtro por estado
            if ($request->has('estado')) {
                $query->where('usuario.estado', $request->estado);
            }

            $propietarios = $query->get();

            return response()->json([
                'data' => $propietarios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar propietarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo propietario
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:propietario,email',
                'password' => 'required|min:8',
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'fecha_nacimiento' => 'nullable|date',
                'direccion' => 'nullable|string',
                'estado' => 'nullable|in:activo,inactivo,suspendido',
                'rol' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Crear usuario base
            $userId = DB::table('user')->insertGetId([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'direccion' => $request->direccion,
                'estado' => $request->estado ?? 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Crear propietario
            $propietarioId = DB::table('propietario')->insertGetId([
                'user_id' => $userId,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rol' => $request->rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Obtener el propietario creado con sus datos de usuario
            $propietario = DB::table('propietario')
                ->join('user', 'propietario.user_id', '=', 'user.id')
                ->where('propietario.id', $propietarioId)
                ->select('propietario.*', 'user.nombre', 'user.apellido', 'user.telefono', 
                        'user.fecha_nacimiento', 'user.direccion', 'user.estado')
                ->first();

            return response()->json([
                'message' => 'Propietario creado exitosamente',
                'data' => $propietario
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear propietario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un propietario específico
     */
    public function show($id)
    {
        try {
            $propietario = DB::table('propietario')
                ->join('user', 'propietario.user_id', '=', 'user.id')
                ->where('propietario.id', $id)
                ->select('propietario.*', 'user.nombre', 'user.apellido', 'user.telefono', 
                        'user.fecha_nacimiento', 'user.direccion', 'user.estado')
                ->first();

            if (!$propietario) {
                return response()->json([
                    'message' => 'Propietario no encontrado'
                ], 404);
            }

            return response()->json([
                'data' => $propietario
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener propietario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un propietario
     */
    public function update(Request $request, $id)
    {
        try {
            // Verificar que existe
            $propietario = DB::table('propietario')->where('id', $id)->first();

            if (!$propietario) {
                return response()->json([
                    'message' => 'Propietario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email|unique:propietario,email,' . $id,
                'password' => 'nullable|min:8',
                'nombre' => 'nullable|string|max:255',
                'apellido' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'fecha_nacimiento' => 'nullable|date',
                'direccion' => 'nullable|string',
                'estado' => 'nullable|in:activo,inactivo,suspendido',
                'rol' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar usuario si hay datos
            $datosUsuario = [];
            if ($request->has('nombre')) $datosUsuario['nombre'] = $request->nombre;
            if ($request->has('apellido')) $datosUsuario['apellido'] = $request->apellido;
            if ($request->has('telefono')) $datosUsuario['telefono'] = $request->telefono;
            if ($request->has('fecha_nacimiento')) $datosUsuario['fecha_nacimiento'] = $request->fecha_nacimiento;
            if ($request->has('direccion')) $datosUsuario['direccion'] = $request->direccion;
            if ($request->has('estado')) $datosUsuario['estado'] = $request->estado;

            if (!empty($datosUsuario)) {
                $datosUsuario['updated_at'] = now();
                DB::table('user')
                    ->where('id', $propietario->user_id)
                    ->update($datosUsuario);
            }

            // Actualizar propietario si hay datos
            $datosPropietario = [];
            if ($request->has('email')) $datosPropietario['email'] = $request->email;
            if ($request->has('password')) $datosPropietario['password'] = Hash::make($request->password);
            if ($request->has('rol')) $datosPropietario['rol'] = $request->rol;

            if (!empty($datosPropietario)) {
                $datosPropietario['updated_at'] = now();
                DB::table('propietario')
                    ->where('id', $id)
                    ->update($datosPropietario);
            }

            DB::commit();

            // Obtener el propietario actualizado
            $propietario = DB::table('propietario')
                ->join('user', 'propietario.user_id', '=', 'user.id')
                ->where('propietario.id', $id)
                ->select('propietario.*', 'user.nombre', 'user.apellido', 'user.telefono', 
                        'user.fecha_nacimiento', 'user.direccion', 'user.estado')
                ->first();

            return response()->json([
                'message' => 'Propietario actualizado exitosamente',
                'data' => $propietario
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar propietario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un propietario
     */
    public function destroy($id)
    {
        try {
            $propietario = DB::table('propietario')->where('id', $id)->first();

            if (!$propietario) {
                return response()->json([
                    'message' => 'Propietario no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            $userId = $propietario->user_id;

            // Eliminar propietario
            DB::table('propietario')->where('id', $id)->delete();

            // Eliminar usuario
            DB::table('user')->where('id', $userId)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Propietario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar propietario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}