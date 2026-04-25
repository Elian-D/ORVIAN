<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ConversationsController extends Controller
{
    public function index(): View
    {
        // Owner / TechnicalSupport — acceso directo al panel admin de Chatwoot sin SSO.
        // Estos usuarios tienen credenciales propias de Chatwoot y no requieren Identity Verification.
        $chatwootAdminUrl = config('communications.chatwoot.base_url');

        return view('admin.conversations.index', compact('chatwootAdminUrl'));
    }
}
