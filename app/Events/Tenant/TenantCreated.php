<?php

namespace App\Events\Tenant;

use App\Models\Tenant\School;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public School $school
    ) {}
}