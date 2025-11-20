<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propietario;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login con email y password (soporta Propietarios y Tutores)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        // Intentar autenticar como Propietario
        $propietario = Propietario::obtenerPorEmail($request->email);
        
        if ($propietario && Hash::check($request->password, $propietario->password)) {
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
                    'tipo_usuario' => 'propietario',
                ]
            ], 200);
        }
        
        // Intentar autenticar como Tutor
        $tutor = Tutor::obtenerPorEmail($request->email);
        
        if ($tutor && Hash::check($request->password, $tutor->password)) {
            // Cargar modelo Eloquent para usar HasApiTokens
            $tutorModel = Tutor::find($tutor->id);
            
            // Crear token
            $token = $tutorModel->createToken('api-token')->plainTextToken;
        
            // Obtener datos completos
            $tutorCompleto = Tutor::obtenerPorId($tutor->id);
        
            return response()->json([
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => [
                    'id' => $tutorCompleto->id,
                    'nombre' => $tutorCompleto->nombre,
                    'apellido' => $tutorCompleto->apellido,
                    'email' => $tutorCompleto->email,
                    'rol' => $tutorCompleto->rol,
                    'estado' => $tutorCompleto->estado,
                    'grado' => $tutorCompleto->grado,
                    'tipo_usuario' => 'tutor',
                ]
            ], 200);
        }
        
        // Si no se encontró en ninguna tabla
        throw ValidationException::withMessages([
            'email' => ['Las credenciales son incorrectas.'],
        ]);
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
        $user = $request->user();
        
        // Determinar si es Propietario o Tutor
        if ($user instanceof Propietario) {
            $userData = Propietario::obtenerPorId($user->id);
            
            return response()->json([
                'user' => [
                    'id' => $userData->id,
                    'nombre' => $userData->nombre,
                    'apellido' => $userData->apellido,
                    'email' => $userData->email,
                    'rol' => $userData->rol,
                    'estado' => $userData->estado,
                    'telefono' => $userData->telefono,
                    'direccion' => $userData->direccion,
                    'tipo_usuario' => 'propietario',
                ]
            ], 200);
        }
        
        if ($user instanceof Tutor) {
            $userData = Tutor::obtenerPorId($user->id);
            
            return response()->json([
                'user' => [
                    'id' => $userData->id,
                    'nombre' => $userData->nombre,
                    'apellido' => $userData->apellido,
                    'email' => $userData->email,
                    'rol' => $userData->rol,
                    'estado' => $userData->estado,
                    'telefono' => $userData->telefono,
                    'direccion' => $userData->direccion,
                    'grado' => $userData->grado,
                    'tipo_usuario' => 'tutor',
                ]
            ], 200);
        }
        
        return response()->json([
            'message' => 'Usuario no encontrado'
        ], 404);
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