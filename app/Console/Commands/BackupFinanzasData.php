<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class BackupFinanzasData extends Command
{
    protected $signature = 'finanzas:backup {--keep=30 : Días de respaldos a conservar}';

    protected $description = 'Respalda en un .zip los datos financieros de cada usuario (ya cifrados) y la base de datos, y elimina respaldos más viejos que --keep días.';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $zipPath = $backupDir . '/backup-' . now()->format('Y-m-d_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo de respaldo: {$zipPath}");
            return self::FAILURE;
        }

        $archivosDatos = glob(storage_path('app/finanzas_data_*.json')) ?: [];
        foreach ($archivosDatos as $file) {
            $zip->addFile($file, 'finanzas_data/' . basename($file));
        }

        $sqlitePath = database_path('database.sqlite');
        if (file_exists($sqlitePath)) {
            $zip->addFile($sqlitePath, 'database.sqlite');
        }

        $zip->close();

        $this->info('Respaldo creado: ' . basename($zipPath) . ' (' . count($archivosDatos) . ' usuarios + base de datos)');

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
