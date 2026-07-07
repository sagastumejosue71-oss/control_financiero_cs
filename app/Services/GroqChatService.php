<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqChatService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.groq.api_key');
        $this->model  = (string) config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    public function disponible(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @param array<int, array{role: string, content: string}> $mensajes historial completo, en orden (system ya incluido)
     */
    public function responder(array $mensajes): string
    {
        if (!$this->disponible()) {
            throw new RuntimeException('GROQ_API_KEY no está configurada.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => $this->model,
                'messages'    => $mensajes,
                'temperature' => 0.5,
                'max_tokens'  => 900,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Groq respondió con error ' . $response->status() . ': ' . $response->body()
            );
        }

        $contenido = $response->json('choices.0.message.content');
        if (!is_string($contenido) || $contenido === '') {
            throw new RuntimeException('Groq no devolvió una respuesta utilizable.');
        }

        return $contenido;
    }
}
