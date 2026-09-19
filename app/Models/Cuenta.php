<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'tipo', 'saldo_inicial', 'activa'])]
class Cuenta extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'activa' => 'boolean',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    /**
     * Saldo real = saldo inicial +/- todos los movimientos. Se calcula
     * siempre desde los movimientos (nunca se guarda un total aparte)
     * para que jamás se desincronice con la realidad.
     *
     * Nota: se usa float, no bcmath, porque el Dockerfile de este proyecto
     * no instala la extensión bcmath (solo pdo/zip/mbstring/dom); agregar
     * bcadd/bcsub aquí habría roto el deploy en Render.
     */
    public function saldoActual(): float
    {
        $ingresos = (float) $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $gastos = (float) $this->movimientos()->where('tipo', 'gasto')->sum('monto');

        return round((float) $this->saldo_inicial + $ingresos - $gastos, 2);
    }
}
