<div class="space-y-8">
    @if(empty($this->academicStructure))
        {{-- Empty State --}}
        <div class="py-20 text-center">
            <x-heroicon-o-academic-cap class="w-16 h-16 mx-auto text-slate-300 dark:text-gray-700 mb-4" />
            <p class="text-slate-400 dark:text-gray-600 font-medium">
                No se encontraron grados configurados para esta institución.
            </p>
        </div>
    @else
        {{-- Tabs por Tanda --}}
        <div 
            x-data="{ activeShift: '{{ array_key_first($this->academicStructure) }}' }"
            class="space-y-6">
            
            {{-- Navegación de Tabs --}}
            <div class="flex flex-wrap border-b border-slate-200 dark:border-gray-800 gap-4">
                @foreach($this->academicStructure as $shiftType => $shiftData)
                    <button 
                        @click="activeShift = '{{ $shiftType }}'"
                        class="pb-4 px-4 transition-all font-bold text-sm relative group"
                        :class="{
                            'text-orvian-orange': activeShift === '{{ $shiftType }}',
                            'text-slate-500 dark:text-gray-500 hover:text-orvian-navy dark:hover:text-white': activeShift !== '{{ $shiftType }}'
                        }">
                        
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clock class="w-4 h-4" />
                            <span>{{ $shiftType }}</span>
                            <x-ui.badge variant="info" size="sm">
                                {{ $shiftData['sections_count'] }}
                                <span>secciones</span>
                            </x-ui.badge>
                        </div>

                        {{-- Indicador de borde inferior --}}
                        <div 
                            class="absolute bottom-0 left-0 w-full h-0.5 transition-all"
                            :class="{
                                'bg-orvian-orange': activeShift === '{{ $shiftType }}',
                                'bg-transparent group-hover:bg-slate-300 dark:group-hover:bg-gray-700': activeShift !== '{{ $shiftType }}'
                            }">
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Contenido de cada Tab --}}
            @foreach($this->academicStructure as $shiftType => $shiftData)
                <div 
                    x-show="activeShift === '{{ $shiftType }}'"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="space-y-12">
                    
                    {{-- Badge de información de la tanda --}}
                    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-orvian-blue/5 to-orvian-orange/5 dark:from-orvian-blue/10 dark:to-orvian-orange/10 rounded-xl border border-dashed border-orvian-orange/30">
                        <x-heroicon-s-clock class="w-6 h-6 text-orvian-orange" />
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                Horario: {{ $shiftData['shift']->start_time->format('h:i A') }} - {{ $shiftData['shift']->end_time->format('h:i A') }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                Total de {{ $shiftData['sections_count'] }} secciones activas
                            </p>
                        </div>
                    </div>

                    {{-- Iterar por Niveles dentro de esta Tanda --}}
                    @forelse($shiftData['levels'] as $nivel => $ciclos)
                        <div class="space-y-8">
                            {{-- Encabezado de Nivel --}}
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">
                                    {{ str_contains($nivel, 'Primaria') ? '👦' : (str_contains($nivel, 'Inicial') ? '👶' : '🎓') }}
                                </span>
                                <h2 class="text-2xl font-bold text-orvian-navy dark:text-white tracking-tight">
                                    {{ $nivel }}
                                </h2>
                                <div class="flex-grow border-t border-slate-200 dark:border-gray-800/50 ml-4"></div>
                            </div>

                            @foreach($ciclos as $ciclo => $familias)
                                <div class="space-y-4">
                                    {{-- Separador de Ciclo --}}
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $ciclo === 'Primer Ciclo' ? 'bg-orange-500' : 'bg-green-500' }}"></span>
                                        <h3 class="text-[11px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                            {{ $ciclo }} {{ (str_contains($nivel, 'Secundario') && $ciclo === 'Segundo Ciclo') ? '(TÉCNICO)' : '' }}
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
                                                            // Lógica de colores dinámica
                                                            $borderColors = [
                                                                1 => 'border-l-blue-500',
                                                                2 => 'border-l-orange-500',
                                                                4 => 'border-l-green-500',
                                                                5 => 'border-l-yellow-500',
                                                            ];
                                                            $borderClass = $grado['is_technical'] 
                                                                ? ($borderColors[$grado['family_id']] ?? 'border-l-slate-400') 
                                                                : 'border-l-transparent';
                                                        @endphp

                                                        <div class="bg-white dark:bg-dark-card rounded-2xl p-4 border border-slate-200 dark:border-gray-800 shadow-sm transition-all hover:shadow-md {{ $borderClass }} border-l-4">
                                                            <div class="flex justify-between items-start mb-4">
                                                                <div>
                                                                    <h5 class="text-xl font-extrabold text-orvian-navy dark:text-white leading-none">
                                                                        {{ $grado['title'] }}
                                                                    </h5>
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
                                                                    <span class="text-[10px] text-slate-400 italic">Vacío</span>
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
                        <div class="py-12 text-center">
                            <p class="text-slate-400 dark:text-gray-600">
                                No hay grados configurados en esta tanda.
                            </p>
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif
</div>