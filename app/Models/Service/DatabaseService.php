<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Exception;

class DatabaseService
{
    /**
     * Probar la conexión a la base de datos
     * 
     * @return array
     */
    public static function testConnection(): array
    {
        try {
            DB::connection()->getPdo();
            
            return [
                'success' => true,
                'message' => 'Conexión exitosa a la base de datos',
                'database' => DB::connection()->getDatabaseName(),
                'driver' => DB::connection()->getDriverName()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al conectar a la base de datos',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información de la conexión
     * 
     * @return array
     */
    public static function getConnectionInfo(): array
    {
        try {
            $config = config('database.connections.' . config('database.default'));
            
            return [
                'driver' => $config['driver'],
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['database'],
                'username' => $config['username'],
                'charset' => $config['charset'] ?? 'utf8'
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si todas las tablas existen
     * 
     * @return array
     */
    public static function checkTables(): array
    {
        try {
            $requiredTables = ['user', 'propietario', 'alumno', 'tutor'];
            $existingTables = [];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }

            return [
                'success' => count($missingTables) === 0,
                'existing_tables' => $existingTables,
                'missing_tables' => $missingTables,
                'message' => count($missingTables) === 0 
                    ? 'Todas las tablas existen' 
                    : 'Faltan tablas por crear'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar migraciones pendientes
     * 
     * @return array
     */
    public static function runMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            
            return [
                'success' => true,
                'message' => 'Migraciones ejecutadas correctamente',
                'output' => Artisan::output()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}