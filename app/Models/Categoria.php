<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nombre', 'tipo'])]
class Categoria extends Model
{
    use HasFactory;

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
