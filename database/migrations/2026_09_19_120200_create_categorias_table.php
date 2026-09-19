<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo'); // ingreso | gasto
            $table->timestamps();

            $table->unique(['negocio_id', 'nombre', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
