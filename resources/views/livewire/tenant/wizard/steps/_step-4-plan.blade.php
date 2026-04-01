<div x-show="$wire.step === 4" style="display:none;" class="space-y-10">

    {{-- ── Encabezado ──────────────────────────────────────────── --}}
    <div class="text-center">
        <p class="text-[11px] font-black uppercase tracking-[0.3em] text-orvian-orange mb-2">Suscripción</p>
        <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tighter">Selecciona un plan para Orvian</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
            Escala tu centro educativo con las herramientas adecuadas. Todos los planes incluyen actualizaciones automáticas.
        </p>
    </div>

    {{-- ── Selector de Ciclo de Facturación ──────────────────────── --}}
    <div class="flex items-center justify-center gap-6 py-4">
        <span class="text-sm font-bold transition-colors" :class="!$wire.billingAnnual ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
            Pago Mensual
        </span>

        <button
            type="button"
            role="switch"
            @click="$wire.set('billingAnnual', !$wire.billingAnnual)"
            :class="$wire.billingAnnual ? 'bg-orvian-orange' : 'bg-slate-200 dark:bg-slate-700'"
            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none ring-offset-2 focus:ring-2 focus:ring-orvian-orange dark:ring-offset-dark-bg"
        >
            <span :class="$wire.billingAnnual ? 'translate-x-5' : 'translate-x-0'"
                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out">
            </span>
        </button>

        <div class="flex items-center gap-3">
            <span class="text-sm font-bold transition-colors" :class="$wire.billingAnnual ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                Pago Anual
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-tighter">
                -20% OFF
            </span>
        </div>
    </div>

    {{-- ── Grid de Planes Dinámicos ──────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @foreach($this->plans as $plan)
            @php
                $isSelected = $plan_id === $plan->id;
                
                // Lógica de Precios para la Card
                $basePrice = (float) $plan->price;
                $calculatedPrice = $billingAnnual ? ($basePrice * 12 * 0.8) : $basePrice;
                
                // Sobrescribimos temporalmente el atributo price para que el componente lo use
                $plan->price = $calculatedPrice;
                
                $savingAmount = ($basePrice * 12) * 0.2;
            @endphp

            <div class="relative group">
                {{-- Badge de Selección (Abajo al medio como el de Estado) --}}
                @if($isSelected)
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-1.5 bg-orvian-orange text-white px-4 py-1.5 rounded-xl shadow-xl shadow-orvian-orange/30 border-2 border-white dark:border-dark-card">
                        <x-heroicon-s-check-circle class="w-4 h-4" />
                        <span class="text-[10px] font-black uppercase tracking-widest">Seleccionado</span>
                    </div>
                @endif

                <div @class([
                    'h-full transition-all duration-500',
                    'scale-[1.03]' => $isSelected,
                    'opacity-60 grayscale-[0.5] scale-[0.98]' => $plan_id && !$isSelected
                ])>
                    <x-ui.plan-card :plan="$plan" :showActions="true">
                        {{-- Override de Precio y Footer mediante el Slot --}}
                        <div class="space-y-4">
                            @if($billingAnnual && $basePrice > 0)
                                <div class="p-2 rounded-lg bg-emerald-500/5 border border-emerald-500/10 text-center">
                                    <p class="text-[10px] font-black text-emerald-500 uppercase">
                                        Ahorras USD$ {{ number_format($savingAmount, 0) }} al año
                                    </p>
                                </div>
                            @endif

                            <x-ui.button 
                                wire:click="$set('plan_id', {{ $plan->id }})"
                                :variant="$isSelected ? 'primary' : 'secondary'"
                                :type="$isSelected ? 'solid' : 'ghost'"
                                class="w-full rounded-2xl font-black py-4"
                            >
                                {{ $isSelected ? 'Plan Seleccionado' : 'Elegir ' . $plan->name }}
                            </x-ui.button>
                        </div>
                    </x-ui.plan-card>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── Footer Informativo & Errores ────────────────────────────── --}}
    <div class="space-y-6">
        @error('plan_id') 
            <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-xl p-4 flex gap-3 animate-shake">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500 shrink-0" />
                <p class="text-sm text-red-700 dark:text-red-300 font-bold">Debes seleccionar un plan para continuar.</p>
            </div>
        @enderror

        <div class="bg-blue-50 dark:bg-slate-900/40 border border-blue-100 dark:border-white/5 rounded-2xl p-6 flex items-start gap-5 shadow-sm">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500" />
            </div>
            <div class="space-y-1">
                <h4 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-tight">Periodo de Gracia Activo</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Al ser un centro nuevo, la facturación se encuentra pausada. Podrás explorar todas las funciones del plan elegido sin cargos hasta que inicies formalmente tu primer periodo académico.
                </p>
            </div>
        </div>
    </div>
</div>