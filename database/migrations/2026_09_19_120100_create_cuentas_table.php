<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 'tipo' se guarda como string (no enum nativo): el resto del proyecto
     * tampoco usa enum en columnas (ver expansiones/finanzas_data) y así
     * evitamos diferencias de comportamiento entre SQLite (local) y
     * Postgres (Render). La validación real vive en el controlador.
     */
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo')->default('banco'); // efectivo | banco | tarjeta | otro
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['negocio_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
