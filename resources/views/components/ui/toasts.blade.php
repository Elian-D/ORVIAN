<div>
    
    @if (session('success'))
        <x-ui.toast-item type="success" title="¡Éxito!" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-ui.toast-item type="error" title="Error" :message="session('error')" :duration="8000" />
    @endif

    @if (session('info'))
        <x-ui.toast-item type="info" title="Información" :message="session('info')" />
    @endif

    @if (session('warning'))
        <x-ui.toast-item type="warning" title="Información" :message="session('warning')" :duration="8000"/>
    @endif

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        @php
            $errorCount = $errors->count();
            $firstError = $errors->first();
            $title = $errorCount > 1 
                ? "Error de validación (+" . ($errorCount - 1) . " más)" 
                : "Error de validación";
        @endphp
        
        <x-ui.toast-item 
            type="error" 
            :title="$title"
            :message="$firstError"
            :duration="8000" 
        />
    @endif

</div>