<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Communications\ChatwootService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConversationsController extends Controller
{
    public function index(ChatwootService $chatwoot): View
    {
        $user         = Auth::user();
        $chatwootBase = config('communications.chatwoot.base_url');

        // Director / Agente — SSO automático vía Identity Verification (HMAC-SHA256)
        $identifierHash = $chatwoot->generateIdentifierHash($user->email);

        $chatwootUrl = "{$chatwootBase}?email=" . urlencode($user->email)
            . "&identifier_hash={$identifierHash}"
            . "&name=" . urlencode($user->name);

        return view('app.conversations.index', compact('chatwootUrl'));
    }
}