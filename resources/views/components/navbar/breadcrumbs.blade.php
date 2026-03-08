@php
    $segments = request()->segments();
    $url = '';
    // Filtramos segmentos que no queremos mostrar (ej. el prefijo de idioma o 'admin')
    $ignoredSegments = ['admin', 'dashboard']; 
@endphp

<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 text-xs font-medium tracking-wide">
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-orvian-blue dark:hover:text-orvian-orange transition flex items-center">
                <x-heroicon-s-home class="w-3.5 h-3.5 mr-1" />
                Dashboard
            </a>
        </li>
        
        @foreach($segments as $segment)
            @php 
                $url .= '/' . $segment;
            @endphp

            @if(!in_array(strtolower($segment), $ignoredSegments) && !is_numeric($segment))
                <li>
                    <div class="flex items-center text-gray-400">
                        <x-heroicon-s-chevron-right class="w-3 h-3 mx-1 text-gray-300 dark:text-gray-600" />
                        <a href="{{ url($url) }}" class="capitalize hover:text-orvian-blue dark:hover:text-orvian-orange transition">
                            {{ str_replace(['-', '_'], ' ', $segment) }}
                        </a>
                    </div>
                </li>
            @endif
        @endforeach
    </ol>
</nav>