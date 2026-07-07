<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMensaje extends Model
{
    protected $table = 'chat_mensajes';

    protected $fillable = ['user_id', 'role', 'contenido'];

    /** El cifrado/descifrado ocurre solo, usando APP_KEY, igual que las sesiones. */
    protected function casts(): array
    {
        return [
            'contenido' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
