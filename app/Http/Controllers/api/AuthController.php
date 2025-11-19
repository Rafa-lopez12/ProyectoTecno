<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login con email y password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $propietario = Propietario::obtenerPorEmail($request->email);
    
        if (!$propietario || !Hash::check($request->password, $propietario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }
    
        // Cargar modelo Eloquent para usar HasApiTokens
        $propietarioModel = Propietario::find($propietario->id);
        
        // Crear token
        $token = $propietarioModel->createToken('api-token')->plainTextToken;
    
        // Obtener datos completos
        $propietarioCompleto = Propietario::obtenerPorId($propietario->id);
    
        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => [
                'id' => $propietarioCompleto->id,
                'nombre' => $propietarioCompleto->nombre,
                'apellido' => $propietarioCompleto->apellido,
                'email' => $propietarioCompleto->email,
                'rol' => $propietarioCompleto->rol,
                'estado' => $propietarioCompleto->estado,
            ]
        ], 200);
    }

    /**
     * Logout - Revocar token actual
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ], 200);
    }

    /**
     * Logout de todos los dispositivos
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Sesiones cerradas en todos los dispositivos'
        ], 200);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        $propietario = Propietario::obtenerPorId($request->user()->id);

        return response()->json([
            'user' => [
                'id' => $propietario->id,
                'nombre' => $propietario->nombre,
                'apellido' => $propietario->apellido,
                'email' => $propietario->email,
                'rol' => $propietario->rol,
                'estado' => $propietario->estado,
                'telefono' => $propietario->telefono,
                'direccion' => $propietario->direccion,
            ]
        ], 200);
    }

    /**
     * Refrescar token
     */
    public function refresh(Request $request)
    {
        // Eliminar token actual
        $request->user()->currentAccessToken()->delete();

        // Crear nuevo token
        $token = $request->user()->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Token refrescado',
            'token' => $token
        ], 200);
    }
}