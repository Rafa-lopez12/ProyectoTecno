<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propietario;
use App\Models\Tutor;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login unificado - soporta Email+Password (Propietario/Tutor) o CI+CI (Alumno)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);
    
        // Intentar autenticar como Propietario
        $propietario = Propietario::obtenerPorEmail($request->email);
        
        if ($propietario && Hash::check($request->password, $propietario->password)) {
            $propietarioModel = Propietario::find($propietario->id);
            $token = $propietarioModel->createToken('api-token')->plainTextToken;
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
            $tutorModel = Tutor::find($tutor->id);
            $token = $tutorModel->createToken('api-token')->plainTextToken;
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
        
        // Intentar autenticar como Alumno (CI en campo email, password debe ser igual al CI)
        $alumno = Alumno::obtenerPorCI($request->email);
        
        if ($alumno && $request->password === $alumno->ci) {
            $alumnoModel = Alumno::find($alumno->id);
            $token = $alumnoModel->createToken('api-token')->plainTextToken;
            $alumnoCompleto = Alumno::obtenerPorId($alumno->id);
        
            return response()->json([
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => [
                    'id' => $alumnoCompleto->id,
                    'nombre' => $alumnoCompleto->nombre,
                    'apellido' => $alumnoCompleto->apellido,
                    'ci' => $alumnoCompleto->ci,
                    'grado_escolar' => $alumnoCompleto->grado_escolar,
                    'estado' => $alumnoCompleto->estado,
                    'tipo_usuario' => 'alumno',
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

        if ($user instanceof Alumno) {
            $userData = Alumno::obtenerPorId($user->id);
            
            return response()->json([
                'user' => [
                    'id' => $userData->id,
                    'nombre' => $userData->nombre,
                    'apellido' => $userData->apellido,
                    'ci' => $userData->ci,
                    'grado_escolar' => $userData->grado_escolar,
                    'estado' => $userData->estado,
                    'telefono' => $userData->telefono,
                    'direccion' => $userData->direccion,
                    'tipo_usuario' => 'alumno',
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
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Token refrescado',
            'token' => $token
        ], 200);
    }
}