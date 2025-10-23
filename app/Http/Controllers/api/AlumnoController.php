<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AlumnoController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Alumno::with(['user', 'tutor.user', 'propietario.user']);

        if ($request->has('grado_escolar')) {
            $query->where('grado_escolar', $request->grado_escolar);
        }

        if ($request->has('grupo')) {
            $query->where('grupo', $request->grupo);
        }

        if ($request->has('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        $perPage = $request->get('per_page', 15);
        $alumnos = $query->paginate($perPage);

        return response()->json($alumnos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Datos del usuario
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'required|date',
            'direccion' => 'nullable|string',
            // Datos del alumno
            'matricula' => 'nullable|string|unique:alumnos,matricula',
            'grado_escolar' => 'nullable|string|max:50',
            'grupo' => 'nullable|string|max:50',
            'fecha_ingreso' => 'required|date',
            'tutor_id' => 'nullable|exists:tutores,id',
            'propietario_id' => 'nullable|exists:propietarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear el usuario
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'direccion' => $request->direccion,
                'estado' => 'activo',
            ]);

            // Generar matrícula si no se proporciona
            $matricula = $request->matricula ?? Alumno::generarMatricula();

            // Crear el alumno
            $alumno = Alumno::create([
                'user_id' => $user->id,
                'matricula' => $matricula,
                'grado_escolar' => $request->grado_escolar,
                'grupo' => $request->grupo,
                'fecha_ingreso' => $request->fecha_ingreso,
                'tutor_id' => $request->tutor_id,
                'propietario_id' => $request->propietario_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Alumno creado exitosamente',
                'data' => $alumno->load(['user', 'tutor.user', 'propietario.user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $alumno = Alumno::with(['user', 'tutor.user', 'propietario.user'])->find($id);

        if (!$alumno) {
            return response()->json([
                'message' => 'Alumno no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $alumno
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'message' => 'Alumno no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            // Datos del usuario
            'nombre' => 'nullable|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|string',
            // Datos del alumno
            'grado_escolar' => 'nullable|string|max:50',
            'grupo' => 'nullable|string|max:50',
            'tutor_id' => 'nullable|exists:tutores,id',
            'propietario_id' => 'nullable|exists:propietarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Actualizar usuario si hay datos
            if ($request->hasAny(['nombre', 'apellido', 'telefono', 'fecha_nacimiento', 'direccion'])) {
                $alumno->user->update($request->only([
                    'nombre', 'apellido', 'telefono', 'fecha_nacimiento', 'direccion'
                ]));
            }

            // Actualizar alumno
            $alumno->update($request->only([
                'grado_escolar', 'grupo', 'tutor_id', 'propietario_id'
            ]));

            DB::commit();

            return response()->json([
                'message' => 'Alumno actualizado exitosamente',
                'data' => $alumno->load(['user', 'tutor.user', 'propietario.user'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'message' => 'Alumno no encontrado'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $user = $alumno->user;
            $alumno->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Alumno eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar alumno',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar o cambiar tutor de un alumno
     */
    public function asignarTutor(Request $request, string $id)
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'message' => 'Alumno no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tutor_id' => 'required|exists:tutores,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $alumno->update(['tutor_id' => $request->tutor_id]);

        return response()->json([
            'message' => 'Tutor asignado exitosamente',
            'data' => $alumno->load(['user', 'tutor.user'])
        ]);
    }
}