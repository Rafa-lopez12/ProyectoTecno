<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Horario
{
    /**
     * Validar datos de horario
     */
    public static function validar(array $datos)
    {
        return Validator::make($datos, [
            'dia_semana' => 'required|string|max:20',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'estado' => 'nullable|in:activo,inactivo',
        ]);
    }

    /**
     * Crear horario
     */
    public static function crear(array $datos)
    {
        $validator = self::validar($datos);
        
        if ($validator->fails()) {
            throw new \Exception('Error en datos de horario: ' . $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $horarioId = DB::table('horario')->insertGetId([
                'dia_semana' => $datos['dia_semana'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'estado' => $datos['estado'] ?? 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return self::obtenerPorId($horarioId);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar horario
     */
    public static function actualizar($id, array $datos)
    {
        $horario = self::obtenerPorId($id);
        
        if (!$horario) {
            throw new \Exception('Horario no encontrado');
        }

        DB::beginTransaction();
        try {
            $datosUpdate = [];
            
            if (isset($datos['dia_semana'])) $datosUpdate['dia_semana'] = $datos['dia_semana'];
            if (isset($datos['hora_inicio'])) $datosUpdate['hora_inicio'] = $datos['hora_inicio'];
            if (isset($datos['hora_fin'])) $datosUpdate['hora_fin'] = $datos['hora_fin'];
            if (isset($datos['estado'])) $datosUpdate['estado'] = $datos['estado'];

            if (!empty($datosUpdate)) {
                $datosUpdate['updated_at'] = now();
                DB::table('horario')->where('id', $id)->update($datosUpdate);
            }

            DB::commit();

            return self::obtenerPorId($id);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar horario (cambiar estado a inactivo)
     */
    public static function eliminar($id)
    {
        $horario = self::obtenerPorId($id);
        
        if (!$horario) {
            throw new \Exception('Horario no encontrado');
        }

        DB::beginTransaction();
        try {
            // Cambiar estado a inactivo en lugar de eliminar
            DB::table('horario')
                ->where('id', $id)
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
     * Obtener horario por ID
     */
    public static function obtenerPorId($id)
    {
        return DB::table('horario')->where('id', $id)->first();
    }

    /**
     * Listar todos los horarios (solo activos por defecto)
     */
    public static function listar($filtros = [])
    {
        $query = DB::table('horario');

        // Por defecto solo mostrar activos, a menos que se especifique lo contrario
        if (!isset($filtros['mostrar_inactivos']) || !$filtros['mostrar_inactivos']) {
            $query->where('estado', 'activo');
        }

        if (isset($filtros['dia_semana'])) {
            $query->where('dia_semana', $filtros['dia_semana']);
        }

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('dia_semana', 'ILIKE', "%{$search}%");
            });
        }

        return $query->orderBy('dia_semana')->orderBy('hora_inicio')->get();
    }

    /**
     * Asignar horario a tutor
     */
    public static function asignarATutor($horarioId, $tutorId)
    {
        $horario = self::obtenerPorId($horarioId);
        $tutor = Tutor::obtenerPorIdSimple($tutorId);
        
        if (!$horario) {
            throw new \Exception('Horario no encontrado');
        }
        
        if (!$tutor) {
            throw new \Exception('Tutor no encontrado');
        }

        DB::beginTransaction();
        try {
            // Verificar si ya existe la asignación
            $existe = DB::table('tutor_horario')
                ->where('tutor_id', $tutorId)
                ->where('horario_id', $horarioId)
                ->exists();

            if ($existe) {
                throw new \Exception('El horario ya está asignado a este tutor');
            }

            DB::table('tutor_horario')->insert([
                'tutor_id' => $tutorId,
                'horario_id' => $horarioId,
                'fecha_asignacion' => now(),
            ]);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Desasignar horario de tutor
     */
    public static function desasignarDeTutor($horarioId, $tutorId)
    {
        DB::beginTransaction();
        try {
            $deleted = DB::table('tutor_horario')
                ->where('tutor_id', $tutorId)
                ->where('horario_id', $horarioId)
                ->delete();

            if (!$deleted) {
                throw new \Exception('Asignación no encontrada');
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener horarios de un tutor
     */
    public static function obtenerHorariosDeTutor($tutorId)
    {
        return DB::table('horario')
            ->join('tutor_horario', 'horario.id', '=', 'tutor_horario.horario_id')
            ->where('tutor_horario.tutor_id', $tutorId)
            ->select(
                'horario.*',
                'tutor_horario.fecha_asignacion'
            )
            ->orderBy('horario.dia_semana')
            ->orderBy('horario.hora_inicio')
            ->get();
    }

    /**
     * Obtener tutores de un horario
     */
    public static function obtenerTutoresDeHorario($horarioId)
    {
        return DB::table('tutor')
            ->join('tutor_horario', 'tutor.id', '=', 'tutor_horario.tutor_id')
            ->join('usuario', 'tutor.user_id', '=', 'usuario.id')
            ->where('tutor_horario.horario_id', $horarioId)
            ->select(
                'tutor.*',
                'usuario.nombre',
                'usuario.apellido',
                'usuario.telefono',
                'usuario.estado',
                'tutor_horario.fecha_asignacion'
            )
            ->get();
    }

    /**
     * Cambiar estado de horario
     */
    public static function cambiarEstado($id, $nuevoEstado)
    {
        $estados = ['activo', 'inactivo'];
        
        if (!in_array($nuevoEstado, $estados)) {
            throw new \Exception('Estado inválido');
        }

        return DB::table('horario')->where('id', $id)->update([
            'estado' => $nuevoEstado,
            'updated_at' => now()
        ]);
    }
}