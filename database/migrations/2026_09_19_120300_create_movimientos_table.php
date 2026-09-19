<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo'); // ingreso | gasto
            $table->decimal('monto', 14, 2);
            $table->string('descripcion')->nullable();
            $table->date('fecha');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['negocio_id', 'fecha']);
            $table->index(['cuenta_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
