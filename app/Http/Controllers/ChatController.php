<?php

namespace App\Http\Controllers;

use App\Models\ChatMensaje;
use App\Services\GroqChatService;
use App\Support\FinanzasCrypto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    private const HISTORIAL_MAX = 40; // mensajes que se guardan/recuperan por usuario
    private const CONTEXTO_MAX  = 12; // últimos mensajes que se mandan a Groq como contexto

    public function __construct(private GroqChatService $groq)
    {
    }

    public function index(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $mensajes = ChatMensaje::where('user_id', $userId)
            ->orderBy('created_at')
            ->limit(self::HISTORIAL_MAX)
            ->get(['role', 'contenido', 'created_at'])
            ->map(fn ($m) => [
                'role'    => $m->role,
                'texto'   => $m->contenido,
                'fecha'   => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'disponible' => $this->groq->disponible(),
            'mensajes'   => $mensajes,
        ]);
    }

    public function send(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        if (!$this->groq->disponible()) {
            return response()->json([
                'error' => 'El asistente de IA no está configurado todavía (falta GROQ_API_KEY).',
            ], 503);
        }

        $validated = $request->validate([
            'mensaje' => 'required|string|max:2000',
        ]);

        // Rate limit: máx 20 mensajes cada 10 minutos por usuario (protege el costo/cupo de la API key)
        $key = 'chat-ia.' . $userId;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $segundos = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Demasiados mensajes seguidos. Espera " . ceil($segundos / 60) . " minuto(s).",
            ], 429);
        }
        RateLimiter::hit($key, 600);

        $userMsg = ChatMensaje::create([
            'user_id'   => $userId,
            'role'      => 'user',
            'contenido' => $validated['mensaje'],
        ]);

        $historialReciente = ChatMensaje::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(self::CONTEXTO_MAX)
            ->get(['role', 'contenido'])
            ->reverse()
            ->values();

        $mensajesParaGroq = array_merge(
            [['role' => 'system', 'content' => $this->promptSistema($userId)]],
            $historialReciente->map(fn ($m) => ['role' => $m->role, 'content' => $m->contenido])->all()
        );

        try {
            $respuesta = $this->groq->responder($mensajesParaGroq);
        } catch (\Throwable $e) {
            Log::channel('auditoria')->warning('CHAT_IA_ERROR', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'No se pudo obtener respuesta del asistente. Intenta de nuevo en un momento.',
            ], 502);
        }

        $assistantMsg = ChatMensaje::create([
            'user_id'   => $userId,
            'role'      => 'assistant',
            'contenido' => $respuesta,
        ]);

        // Poda: no dejar crecer el historial sin límite.
        $total = ChatMensaje::where('user_id', $userId)->count();
        if ($total > self::HISTORIAL_MAX) {
            $idsAConservar = ChatMensaje::where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(self::HISTORIAL_MAX)
                ->pluck('id');
            ChatMensaje::where('user_id', $userId)->whereNotIn('id', $idsAConservar)->delete();
        }

        return response()->json([
            'respuesta' => $respuesta,
            'fecha'     => $assistantMsg->created_at->toIso8601String(),
        ]);
    }

    public function clear(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        ChatMensaje::where('user_id', $userId)->delete();
        return response()->json(['ok' => true]);
    }

    private function promptSistema(int $userId): string
    {
        $resumen = $this->resumenFinanciero($userId);

        return <<<PROMPT
            Eres el asistente financiero de "Finanzas GT", una app de control financiero personal para usuarios en Guatemala (moneda principal: Quetzales, Q).

            Tu rol:
            - Ayudar a planear pagos de deudas, presupuestos y metas de ahorro usando los datos reales del usuario que se te dan abajo.
            - Responder dudas generales de finanzas personales y educación básica sobre inversión y mercados bursátiles.
            - Ser claro, práctico y concreto: da pasos accionables, no solo teoría.

            Límites importantes que SIEMPRE debes respetar:
            - NO tienes acceso a internet ni a datos en tiempo real. Si preguntan por el precio actual de una acción, cripto o índice bursátil, ACLARA que no puedes ver precios en vivo y que consulten una fuente actualizada (ej. Google Finance, su broker) — nunca inventes un número.
            - No eres un asesor financiero certificado. Para decisiones grandes (inversiones importantes, temas legales/fiscales), recuérdalo brevemente.
            - Responde en español, tono cercano pero profesional. Sé conciso — evita respuestas kilométricas salvo que pidan un plan detallado.

            Datos financieros reales del usuario (úsalos para personalizar tus respuestas):
            {$resumen}
            PROMPT;
    }

    private function resumenFinanciero(int $userId): string
    {
        $row = DB::table('finanzas_data')->where('user_id', $userId)->first();
        if (!$row) {
            return 'El usuario todavía no ha registrado datos financieros en la app.';
        }

        $data = FinanzasCrypto::decode($row->data);
        if (!is_array($data)) {
            return 'No se pudieron leer los datos financieros del usuario.';
        }

        $tasa = (float) ($data['exchange_rate'] ?? 7.70);
        $toQ  = function (float $monto, ?string $moneda) use ($tasa) {
            return $moneda === 'USD' ? $monto * $tasa : $monto;
        };
        $mensual = function (float $monto, ?string $frecuencia) {
            return match ($frecuencia) {
                'quincenal' => $monto * 2,
                'semanal'   => $monto * 4.33,
                'diario'    => $monto * 30,
                'anual'     => $monto / 12,
                default     => $monto,
            };
        };

        $ingresos = $data['ingresos'] ?? [];
        $totalIngresos = array_sum(array_map(
            fn ($i) => $toQ($mensual((float) ($i['monto'] ?? 0), $i['frecuencia'] ?? 'mensual'), $i['moneda'] ?? 'GTQ'),
            $ingresos
        ));

        $gastosFijos = $data['gastos_fijos'] ?? [];
        $totalGastosFijos = array_sum(array_map(
            fn ($g) => $toQ((float) ($g['monto'] ?? 0), $g['moneda'] ?? 'GTQ'),
            $gastosFijos
        ));

        $gastosVariables = $data['gastos_variables'] ?? [];
        $totalGastosVariables = array_sum(array_map(
            fn ($g) => $toQ((float) ($g['monto'] ?? 0), $g['moneda'] ?? 'GTQ'),
            $gastosVariables
        ));

        $deudas = $data['deudas'] ?? [];
        $lineasDeudas = array_map(function ($d) use ($toQ) {
            $saldoQ = $toQ((float) ($d['saldo_actual'] ?? 0), $d['moneda'] ?? 'GTQ');
            $nombre = $d['nombre'] ?? 'Deuda';
            $tasaInt = $d['tasa_interes_anual'] ?? 0;
            $pagoObj = $d['pago_objetivo_mensual'] ?? 0;
            return "  - {$nombre}: saldo Q" . number_format($saldoQ, 2) . ", tasa {$tasaInt}% anual, pago objetivo Q" . number_format((float) $pagoObj, 2) . '/mes';
        }, $deudas);
        $totalDeudaQ = array_sum(array_map(fn ($d) => $toQ((float) ($d['saldo_actual'] ?? 0), $d['moneda'] ?? 'GTQ'), $deudas));

        $metas = $data['metas_ahorro'] ?? [];
        $lineasMetas = array_map(function ($m) {
            $objetivo = number_format((float) ($m['monto_objetivo'] ?? 0), 2);
            $actual   = number_format((float) ($m['monto_actual'] ?? 0), 2);
            return "  - {$m['nombre']}: Q{$actual} de Q{$objetivo}" . (!empty($m['fecha_objetivo']) ? " (meta: {$m['fecha_objetivo']})" : '');
        }, $metas);

        $superavit = $totalIngresos - $totalGastosFijos - $totalGastosVariables;

        $texto = "- Ingresos mensuales totales: Q" . number_format($totalIngresos, 2) . "\n";
        $texto .= "- Gastos fijos mensuales: Q" . number_format($totalGastosFijos, 2) . "\n";
        $texto .= "- Gastos variables estimados: Q" . number_format($totalGastosVariables, 2) . "\n";
        $texto .= "- Disponible mensual antes de deudas: Q" . number_format($superavit, 2) . "\n";
        $texto .= "- Deuda total: Q" . number_format($totalDeudaQ, 2) . " (" . count($deudas) . " deuda(s))\n";
        if ($lineasDeudas) {
            $texto .= implode("\n", $lineasDeudas) . "\n";
        }
        if ($lineasMetas) {
            $texto .= "- Metas de ahorro:\n" . implode("\n", $lineasMetas) . "\n";
        }
        $texto .= "- Tipo de cambio configurado: 1 USD = Q" . number_format($tasa, 2);

        return $texto;
    }
}
