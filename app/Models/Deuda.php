<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acreedor', 'monto_total', 'saldo_pendiente', 'tasa_interes', 'fecha_inicio', 'fecha_limite', 'saldada'])]
class Deuda extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'tasa_interes' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_limite' => 'date',
            'saldada' => 'boolean',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
