<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupFinanzasData extends Command
{
    protected $signature = 'finanzas:backup {--keep=30 : Días de respaldos a conservar}';

    protected $description = 'Respalda la base de datos completa (usuarios, expansiones y datos financieros cifrados) en un .zip, y elimina respaldos más viejos que --keep días.';

    /** Tablas con datos reales de usuarios; el resto (migrations, cache, jobs...) no hace falta respaldar. */
    private const TABLAS = ['users', 'expansiones', 'finanzas_data'];

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $zipPath = $backupDir . '/backup-' . now()->format('Y-m-d_His') . '-' . substr(uniqid(), -6) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo de respaldo: {$zipPath}");
            return self::FAILURE;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Copia directa del archivo: ya contiene todas las tablas.
            $sqlitePath = database_path('database.sqlite');
            if (file_exists($sqlitePath)) {
                $zip->addFile($sqlitePath, 'database.sqlite');
            }
            $this->info('Respaldo SQLite (archivo completo) creado: ' . basename($zipPath));
        } else {
            // MySQL/Postgres (ej. Neon): no hay archivo que copiar, así que
            // exportamos cada tabla crítica como JSON. Portable, no depende
            // de tener pg_dump/mysqldump instalado en el servidor.
            // NOTA: en Neon ya existen respaldos/point-in-time recovery propios;
            // esto es una segunda capa de seguridad, no el respaldo principal.
            foreach (self::TABLAS as $tabla) {
                $filas = DB::table($tabla)->get();
                $zip->addFromString($tabla . '.json', $filas->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            $this->info("Respaldo ({$driver}, " . count(self::TABLAS) . ' tablas exportadas) creado: ' . basename($zipPath));
        }

        $zip->close();

        $this->rotar((int) $this->option('keep'), $backupDir);

        return self::SUCCESS;
    }

    private function rotar(int $keepDays, string $backupDir): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $eliminados = 0;

        foreach (glob($backupDir . '/backup-*.zip') ?: [] as $old) {
            if (filemtime($old) < $cutoff) {
                unlink($old);
                $eliminados++;
            }
        }

        if ($eliminados > 0) {
            $this->info("Respaldos con más de {$keepDays} días eliminados: {$eliminados}");
        }
    }
}
