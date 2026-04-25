<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class ChatwootService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected int    $accountId;

    public function __construct()
    {
        $this->baseUrl   = config('communications.chatwoot.base_url');
        $this->apiToken  = config('communications.chatwoot.api_token');
        $this->accountId = config('communications.chatwoot.account_id');
    }

    /**
     * Genera el identifier_hash para SSO/Identity Verification de Chatwoot.
     * Siempre se computa server-side. Nunca debe exponerse al cliente.
     */
    public function generateIdentifierHash(string $email): string
    {
        return hash_hmac('sha256', $email, config('communications.chatwoot.hmac_token'));
    }

    /**
     * Busca un agente por email. Evita duplicados en las Actions de onboarding.
     */
    public function findAgentByEmail(string $email): ?array
    {
        $response = Http::withHeaders(['api_access_token' => $this->apiToken])
            ->get("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/agents");

        if ($response->failed()) {
            return null;
        }

        return collect($response->json())
            ->firstWhere('email', $email);
    }

    /**
     * Crea un agente en Chatwoot.
     * Invocado al final de CompleteOnboardingAction y CompleteTenantOnboardingAction.
     */
    public function createAgent(string $name, string $email, string $role = 'agent'): Response
    {
        return Http::withHeaders(['api_access_token' => $this->apiToken])
            ->post("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/agents", [
                'name'                => $name,
                'email'               => $email,
                'role'                => $role,
                'availability_status' => 'online',
            ]);
    }

    /**
     * Obtiene el conteo de conversaciones abiertas.
     * Usado por el Dashboard para el badge del tile de Conversaciones.
     */
    public function getPendingConversationsCount(): int
    {
        try {
            $response = Http::withHeaders(['api_access_token' => $this->apiToken])
                ->get("{$this->baseUrl}/api/v1/accounts/{$this->accountId}/conversations", [
                    'status'        => 'open',
                    'assignee_type' => 'assigned',
                ]);

            return $response->successful()
                ? ($response->json('data.meta.all_count') ?? 0)
                : 0;

        } catch (\Throwable) {
            return 0;
        }
    }
}