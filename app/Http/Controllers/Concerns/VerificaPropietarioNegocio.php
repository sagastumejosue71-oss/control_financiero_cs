<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Negocio;

/**
 * Punto único donde se verifica dueño de negocio: evita repetir la misma
 * comprobación (y el mismo error) en cinco controladores distintos.
 *
 * - 401 si no hay sesión.
 * - 404 (nunca 403) si el negocio no existe o es de otro usuario, para no
 *   confirmarle a un atacante que ese ID sí existe pero no es suyo (IDOR).
 */
trait VerificaPropietarioNegocio
{
    protected function negocioDelUsuario(int $negocioId): Negocio
    {
        abort_unless(session('user_id'), 401, 'No autenticado');

        $negocio = Negocio::where('id', $negocioId)
            ->where('user_id', session('user_id'))
            ->first();

        abort_if($negocio === null, 404);

        return $negocio;
    }
}
