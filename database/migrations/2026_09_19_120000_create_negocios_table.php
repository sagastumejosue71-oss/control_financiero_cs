<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada fila es un negocio del usuario. Todo lo demás (cuentas,
     * categorías, movimientos, deudas) cuelga de negocio_id, así que
     * el dinero de un negocio nunca se mezcla con el de otro.
     */
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('moneda', 3)->default('GTQ');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
