<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Copia usuarios, expansiones y datos financieros de la base de datos LOCAL
 * (normalmente SQLite, la que usaste mientras desarrollabas) hacia una base
 * de datos remota (ej. Neon) — pensado para usarse UNA sola vez, al migrar
 * de correr localmente a desplegar en la nube.
 *
 * No es destructivo: usa updateOrInsert, así que se puede correr más de una
 * vez sin duplicar nada.
 */
class SyncDataToCloud extends Command
{
    protected $signature = 'finanzas:sync-to-cloud
        {--host= : Host de la base remota}
        {--port=5432 : Puerto}
        {--database= : Nombre de la base remota}
        {--username= : Usuario remoto}
        {--password= : Contraseña remota}
        {--driver=pgsql : pgsql o mysql}
        {--sslmode=require : require, prefer o disable (Neon necesita "require")}';

    protected $description = 'Copia usuarios, expansiones y datos financieros de la base local hacia una base remota (Neon, etc).';

    public function handle(): int
    {
        $host = $this->option('host') ?: $this->ask('Host de la base remota (ej. ep-xxxx.neon.tech)');
        $database = $this->option('database') ?: $this->ask('Nombre de la base de datos');
        $username = $this->option('username') ?: $this->ask('Usuario');
        $password = $this->option('password') ?: $this->secret('Contraseña');
        $port = (int) $this->option('port');
        $driver = $this->option('driver');
        $sslmode = $this->option('sslmode');

        Config::set('database.connections.cloud_sync', [
            'driver'   => $driver,
            'host'     => $host,
            'port'     => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8',
            'sslmode'  => $sslmode,
        ]);

        $this->info('Probando conexión remota...');
        try {
            DB::connection('cloud_sync')->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se pudo conectar a la base remota: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->info('Conexión OK.');

        if (!$this->confirm('¿Corriste "php artisan migrate" contra esta base remota antes de esto?', true)) {
            $this->warn('Corre las migraciones remotas primero (el contenedor de despliegue ya lo hace solo al arrancar).');
            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->sincronizarTabla('users');
        }, 1);

        DB::transaction(function () {
            $this->sincronizarTabla('expansiones');
        }, 1);

        DB::transaction(function () {
            $this->sincronizarTabla('finanzas_data');
        }, 1);

        $this->info('Sincronización completa.');
        return self::SUCCESS;
    }

    private function sincronizarTabla(string $tabla): void
    {
        $filas = DB::table($tabla)->get();
        $bar = $this->output->createProgressBar($filas->count());
        $bar->start();

        foreach ($filas as $fila) {
            $data = (array) $fila;
            $id = $data['id'];
            unset($data['id']);

            DB::connection('cloud_sync')->table($tabla)->updateOrInsert(
                ['id' => $id],
                $data
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$tabla}: {$filas->count()} filas sincronizadas.");
    }
}
