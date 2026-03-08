<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class ToastItem extends Component
{
    public string $bgClass;
    public string $iconClass;
    public string $progressClass;
    public string $icon;

    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public int $duration = 5000
    ) {
        $this->configureAppearance();
    }

    protected function configureAppearance(): void
    {
        switch ($this->type) {
            case 'success':
                $this->bgClass = 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-500';
                $this->iconClass = 'text-emerald-500';
                $this->progressClass = 'bg-emerald-500';
                $this->icon = 'heroicon-s-check-circle';
                break;
            case 'error':
                $this->bgClass = 'bg-red-50 dark:bg-red-500/10 border-red-500';
                $this->iconClass = 'text-red-500';
                $this->progressClass = 'bg-red-500';
                $this->icon = 'heroicon-s-x-circle';
                break;
            case 'warning':
                $this->bgClass = 'bg-amber-50 dark:bg-amber-500/10 border-amber-500';
                $this->iconClass = 'text-amber-500';
                $this->progressClass = 'bg-amber-500';
                $this->icon = 'heroicon-s-exclamation-triangle';
                break;
            case 'info':
            default:
                $this->bgClass = 'bg-blue-50 dark:bg-blue-500/10 border-blue-500';
                $this->iconClass = 'text-blue-500';
                $this->progressClass = 'bg-blue-500';
                $this->icon = 'heroicon-s-information-circle';
                break;
        }
    }

    public function render()
    {
        return view('components.ui.toast-item');
    }
}