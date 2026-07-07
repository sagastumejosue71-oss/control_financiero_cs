<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Cifra/descifra el contenido de los archivos finanzas_data_{id}.json en reposo
 * (usa APP_KEY vía Crypt, igual que Laravel cifra sesiones/cookies).
 *
 * decode() acepta también JSON plano sin cifrar: los archivos creados antes de
 * este cambio se leen igual, y quedan migrados a cifrado en el siguiente guardado.
 */
class FinanzasCrypto
{
    public static function encode(array $data): string
    {
        return Crypt::encryptString(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function decode(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString($raw);
        } catch (DecryptException) {
            // Archivo legacy sin cifrar (de antes de este cambio).
            $json = $raw;
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }
}
