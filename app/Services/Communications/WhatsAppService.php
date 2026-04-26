<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl  = config('communications.evolution.base_url');
        $this->apiKey   = config('communications.evolution.api_key');
        $this->instance = config('communications.evolution.instance_name');
    }

    /**
     * Envía un mensaje de texto al tutor.
     * ORVIAN solo envía — no espera ni procesa respuestas.
     *
     * @param  string  $phone    Número en formato E.164 (ej. +18091234567)
     * @param  string  $message  Texto con soporte de formato WhatsApp (*negrita*, _cursiva_)
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        // Evolution API espera el número sin el símbolo '+'
        $normalizedPhone = ltrim($phone, '+');

        try {
            $response = Http::withHeaders([
                'apikey'       => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number'  => $normalizedPhone,
                'text'    => $message, // Estructura plana, más compatible
                'options' => [
                    'delay'   => 1200,
                    'presence' => 'composing',
                ],
            ]);

            if ($response->successful()) {
                Log::info('WhatsAppService: Mensaje enviado', [
                    'phone'  => $normalizedPhone,
                    'msg_id' => $response->json('key.id'),
                ]);
                return true;
            }

            Log::warning('WhatsAppService: Respuesta no exitosa', [
                'phone'  => $normalizedPhone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsAppService: Excepción al enviar mensaje', [
                'phone' => $normalizedPhone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Verifica si la instancia de WhatsApp está conectada en el VPS.
     * Usado como fallback por WhatsappStatusIndicator cuando la caché no tiene dato.
     */
    public function getInstanceStatus(): array
    {
        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->baseUrl}/instance/connectionState/{$this->instance}");

            return $response->json() ?? ['state' => 'unknown'];
        } catch (\Throwable $e) {
            return ['state' => 'error', 'message' => $e->getMessage()];
        }
    }
}