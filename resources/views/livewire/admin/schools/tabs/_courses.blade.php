<div class="space-y-12">
    @forelse($this->academicStructure as $nivel => $ciclos)
        <div class="space-y-8">
            {{-- Encabezado de Nivel --}}
            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ $nivel === 'Primario' ? '👦' : '🎓' }}</span>
                <h2 class="text-2xl font-bold text-orvian-navy dark:text-white tracking-tight">Nivel {{ $nivel }}</h2>
                <div class="flex-grow border-t border-slate-200 dark:border-gray-800/50 ml-4"></div>
            </div>

            @foreach($ciclos as $ciclo => $familias)
                <div class="space-y-4">
                    {{-- Separador de Ciclo --}}
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $ciclo === 'Primer Ciclo' ? 'bg-orange-500' : 'bg-green-500' }}"></span>
                        <h3 class="text-[11px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                            {{ $ciclo }} {{ ($nivel === 'Secundario' && $ciclo === 'Segundo Ciclo') ? '(TÉCNICO)' : '' }}
                        </h3>
                        <div class="flex-grow border-t border-dashed border-slate-200 dark:border-gray-800 ml-4"></div>
                    </div>

                    {{-- Contenedor de Familias / Grupos --}}
                    <div class="space-y-8 pl-4">
                        @foreach($familias as $familyName => $grados)
                            <div class="group">
                                @if($familyName !== 'General')
                                    <h4 class="text-xs font-bold text-orvian-orange dark:text-orange-400 mb-4 flex items-center gap-2">
                                        <x-heroicon-s-tag class="w-3 h-3" />
                                        {{ $familyName }}
                                    </h4>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($grados as $grado)
                                        @php
                                            // Lógica de colores dinámica según la familia técnica
                                            $borderColors = [
                                                1 => 'border-l-blue-500',   // Informática
                                                2 => 'border-l-orange-500', // Administración
                                                4 => 'border-l-green-500',  // Salud
                                                5 => 'border-l-yellow-500', // Turismo
                                            ];
                                            $borderClass = $grado['is_technical'] ? ($borderColors[$grado['family_id']] ?? 'border-l-slate-400') : 'border-l-transparent';
                                        @endphp

                                        <div class="bg-white dark:bg-dark-card rounded-2xl p-4 border border-slate-200 dark:border-gray-800 shadow-sm transition-all hover:shadow-md {{ $borderClass }} border-l-4">
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h5 class="text-xl font-extrabold text-orvian-navy dark:text-white leading-none">{{ $grado['title'] }}</h5>
                                                    <p class="text-[9px] text-slate-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-1">
                                                        {{ $grado['subtitle'] }}
                                                    </p>
                                                </div>
                                                <x-heroicon-o-academic-cap class="w-4 h-4 text-slate-300 dark:text-gray-700" />
                                            </div>

                                            <div class="flex flex-wrap gap-1.5 mt-auto">
                                                @forelse($grado['sections'] as $section)
                                                    <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-gray-900/80 px-2 py-1 rounded-lg border border-slate-100 dark:border-gray-800">
                                                        <span class="text-[11px] font-black text-orvian-orange">
                                                            {{ $section->label }}
                                                        </span>
                                                        <span class="text-[10px] font-medium text-slate-400 dark:text-gray-600">--</span>
                                                    </div>
                                                @empty
                                                    <span class="text-[10px] text-slate-400 italic">Vacio</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        {{-- Empty State --}}
        <div class="py-20 text-center">
            <p class="text-slate-400 dark:text-gray-600 font-medium">No se encontraron grados configurados para esta institución.</p>
        </div>
    @endforelse
</div>