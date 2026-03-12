            <div class="px-8 py-5 border-t border-slate-200 dark:border-white/5 bg-slate-50/30 dark:bg-white/[0.01] flex items-center justify-between gap-4">

                <x-ui.button
                    variant="secondary"
                    type="outline"
                    iconLeft="heroicon-s-arrow-left"
                    wire:click="prevStep"
                    :class="$step === 1 ? 'opacity-0 pointer-events-none' : ''"
                >
                    Atrás
                </x-ui.button>

                @if($step < $totalSteps)
                    <x-ui.button
                        variant="primary"
                        :hoverEffect="true"
                        iconRight="heroicon-s-arrow-right"
                        wire:click="nextStep"
                        wire:loading.attr="disabled"
                        wire:target="nextStep"
                    >
                        {{ $nextStepLabel ? 'Continuar: ' . $nextStepLabel : 'Continuar' }}
                    </x-ui.button>
                @else
                    <x-ui.button
                        variant="success"
                        :hoverEffect="true"
                        iconLeft="heroicon-s-check-circle"
                        wire:click="finish"
                        wire:loading.attr="disabled"
                        wire:target="finish"
                    >
                        Finalizar Configuración
                    </x-ui.button>
                @endif

            </div>
