<?php

namespace App\View\Components\Ui;

use App\Models\Tenant\Plan;
use Illuminate\View\Component;

class PlanCard extends Component
{
    public function __construct(
        public Plan $plan,
        public bool $showActions = true
    ) {}

    public function render()
    {
        return view('components.ui.plan-card');
    }
}