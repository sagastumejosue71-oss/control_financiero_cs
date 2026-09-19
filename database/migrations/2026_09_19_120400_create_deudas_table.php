<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deudas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->string('acreedor');
            $table->decimal('monto_total', 14, 2);
            $table->decimal('saldo_pendiente', 14, 2);
            $table->decimal('tasa_interes', 5, 2)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->boolean('saldada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deudas');
    }
};
