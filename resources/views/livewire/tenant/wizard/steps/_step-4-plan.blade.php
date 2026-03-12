                <div x-show="$wire.step === 4" style="display:none;" class="space-y-8">

                    {{-- ── Encabezado ──────────────────────────────────────────── --}}
                    <div class="text-center">
                        <p class="text-[11px] font-black uppercase tracking-widest text-orvian-orange mb-1">Suscripción</p>
                        <h3 class="text-xl font-black text-slate-800 dark:text-white">Selecciona un plan para Orvian</h3>
                        <p class="text-xs text-slate-500 mt-2 max-w-md mx-auto">
                            Escala tu centro educativo con las herramientas adecuadas. Todos los planes incluyen actualizaciones automáticas.
                        </p>
                    </div>

                    {{-- ── Selector de Ciclo de Facturación ──────────────────────── --}}
                    <div class="flex items-center justify-center gap-4">
                        <span class="text-sm font-bold transition-colors" :class="!$wire.billingAnnual ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                            Mensual
                        </span>

                        <button
                            type="button"
                            role="switch"
                            @click="$wire.set('billingAnnual', !$wire.billingAnnual)"
                            :class="$wire.billingAnnual ? 'bg-orvian-orange' : 'bg-slate-200 dark:bg-slate-700'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        >
                            <span :class="$wire.billingAnnual ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                            </span>
                        </button>

                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold transition-colors" :class="$wire.billingAnnual ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                                Anual
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-tighter">
                                -20% Descuento
                            </span>
                        </div>
                    </div>

                    {{-- ── Grid de Planes Dinámicos ──────────────────────────────── --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($this->plans as $plan)
                            @php
                                $isSelected = $plan_id === $plan->id;
                                
                                // Lógica de Precios Uniforme
                                $basePrice = (float) $plan->price;
                                $isFree = $basePrice <= 0;
                                
                                // Cálculo: Si es anual, precio * 12 con 20% desc. Si es mensual, precio base.
                                $displayPrice = $billingAnnual 
                                    ? ($basePrice * 12 * 0.8) 
                                    : $basePrice;
                                
                                $savingAmount = ($basePrice * 12) * 0.2;
                            @endphp

                            <div @class([
                                'relative flex flex-col rounded-3xl border-2 transition-all duration-300 bg-white dark:bg-slate-900/40',
                                'border-orvian-orange ring-4 ring-orvian-orange/10 shadow-xl' => $isSelected,
                                'border-slate-100 dark:border-white/5 hover:border-slate-300 dark:hover:border-white/10' => !$isSelected,
                            ])>
                                
                                {{-- Badge de Destacado (Basado en base de datos) --}}
                                @if($plan->is_featured)
                                    <div class="absolute -top-3 inset-x-0 flex justify-center">
                                        <x-ui.badge
                                            variant="primary"
                                            size="sm"
                                            :dot="false"
                                            class="uppercase font-black shadow-lg rounded-full">
                                            Más Popular
                                        </x-ui.badge>
                                    </div>
                                @endif

                                <div class="p-6 flex flex-col h-full">
                                    {{-- Info Básica --}}
                                    <div class="mb-6">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-lg font-black text-slate-800 dark:text-white">{{ $plan->name }}</h4>
                                            @if($isSelected)
                                                <svg class="w-6 h-6 text-orvian-orange" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $plan->description }}</p>
                                    </div>

                                    {{-- Precio Uniforme --}}
                                    <div class="mb-6 h-12 flex flex-col justify-center">
                                        @if($isFree)
                                            <span class="text-3xl font-black text-slate-800 dark:text-white">Gratis</span>
                                        @else
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-sm font-bold text-slate-400">USD$</span>
                                                <span class="text-4xl font-black text-slate-800 dark:text-white tracking-tight">
                                                    {{ number_format($displayPrice, 0) }}
                                                </span>
                                                <span class="text-xs font-medium text-slate-500">
                                                    /{{ $billingAnnual ? 'año' : 'mes' }}
                                                </span>
                                            </div>
                                            
                                            @if($billingAnnual && $savingAmount > 0)
                                                <p class="text-[10px] font-bold text-emerald-500 mt-1">
                                                    Ahorras USD$ {{ number_format($savingAmount, 0) }} este año
                                                </p>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Límites de Uso --}}
                                    <div class="grid grid-cols-2 gap-3 mb-6 p-3 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                                        <div class="text-center border-r border-slate-200 dark:border-white/10">
                                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-tighter">Usuarios</p>
                                            <p class="text-sm font-black text-slate-700 dark:text-slate-200">{{ number_format($plan->limit_users) }}</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-tighter">Estudiantes</p>
                                            <p class="text-sm font-black text-slate-700 dark:text-slate-200">{{ number_format($plan->limit_students) }}</p>
                                        </div>
                                    </div>

                                    {{-- Features List --}}
                                    <ul class="space-y-3 mb-8 flex-1">
                                        @foreach($plan->features as $feature)
                                            <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-400">
                                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>{{ $feature->name }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- Acción Uniforme --}}
                                    <button 
                                        wire:click="$set('plan_id', {{ $plan->id }})"
                                        @class([
                                            'w-full py-3 px-4 rounded-xl text-xs font-black transition-all duration-200 border-2',
                                            'bg-orvian-orange border-orvian-orange text-white shadow-lg shadow-orvian-orange/20' => $isSelected,
                                            'bg-transparent border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:border-orvian-orange hover:text-orvian-orange' => !$isSelected,
                                        ])
                                    >
                                        {{ $isSelected ? 'Plan Seleccionado' : 'Elegir Plan ' . $plan->name }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Error de Validación --}}
                    @error('plan_id') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror

                    {{-- Footer Informativo --}}
                    <div class="bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-2xl p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            <strong>Nota:</strong> Al ser un centro nuevo, podrás disfrutar de un periodo de prueba. La facturación comenzará una vez el centro esté operando con su primer periodo académico activo.
                        </p>
                    </div>
                </div>
