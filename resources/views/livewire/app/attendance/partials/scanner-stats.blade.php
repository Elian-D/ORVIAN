<div class="space-y-4">

    {{-- Último Registrado --}}
    @if($lastRegistered)
        @php $lastStudent = $scannedStudents->get($lastRegistered['student_id'] ?? null); @endphp
        <div class="bg-white dark:bg-dark-card border border-gray-100 dark:border-dark-border rounded-[2rem] overflow-hidden">

            <div class="flex items-center gap-2 px-6 pt-5 pb-3 border-b border-gray-100 dark:border-dark-border">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                    Último Registro
                </span>
            </div>

            <div class="p-6">
                <div class="flex items-center gap-4">
                    @if($lastStudent)
                        <x-ui.student-avatar :student="$lastStudent" size="lg" />
                    @else
                        {{-- Fallback si el modelo no cargó aún --}}
                        <div class="h-14 w-14 rounded-2xl bg-gray-100 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-lg font-black text-gray-500 dark:text-gray-300 uppercase">
                                {{ strtoupper(substr($lastRegistered['name'], 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <h4 class="text-base font-black text-gray-900 dark:text-white leading-tight truncate">
                            {{ $lastRegistered['name'] }}
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                            {{ $lastRegistered['section'] }}
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold
                                {{ $lastRegistered['status'] === 'present'
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : ($lastRegistered['status'] === 'late'
                                        ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}">
                                {{ $lastRegistered['status_label'] }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-medium tabular-nums">
                                {{ $lastRegistered['time'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Historial de Actividad --}}
    <div class="bg-white dark:bg-dark-card border border-gray-100 dark:border-dark-border rounded-[2rem] overflow-hidden">

        <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100 dark:border-dark-border">
            <div class="flex items-center gap-2">
                <x-heroicon-s-clock class="w-4 h-4 text-orvian-orange" />
                <span class="text-[11px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                    Actividad Reciente
                </span>
            </div>
            @if(count($recentScans) > 0)
                <span class="px-2 py-0.5 bg-orvian-orange/10 text-orvian-orange text-xs font-bold rounded-lg">
                    {{ count($recentScans) }}
                </span>
            @endif
        </div>

        <div class="divide-y divide-gray-50 dark:divide-dark-border max-h-[420px] overflow-y-auto">
            @forelse($recentScans as $scan)
                @php $student = $scannedStudents->get($scan['student_id'] ?? null); @endphp
                <div class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">

                    @if($student)
                        <x-ui.student-avatar :student="$student" size="sm" />
                    @else
                        <div class="h-8 w-8 rounded-xl bg-gray-100 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-[10px] font-bold text-gray-600 dark:text-gray-300 uppercase">
                                {{ strtoupper(substr($scan['name'], 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $scan['name'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $scan['section'] }}</p>
                    </div>

                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="w-2 h-2 rounded-full
                            {{ $scan['status'] === 'present'
                                ? 'bg-green-500'
                                : ($scan['status'] === 'late' ? 'bg-yellow-500' : 'bg-blue-500') }}">
                        </span>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium tabular-nums">
                            {{ $scan['time'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="py-14 text-center px-6">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-3">
                        <x-heroicon-o-qr-code class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-400 dark:text-gray-500">
                        Esperando primer escaneo...
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
