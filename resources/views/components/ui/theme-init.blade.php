{{--
    resources/views/components/ui/theme-init.blade.php
    ---------------------------------------------------
    Script síncrono de inicialización de tema.

    ⚠️ DEBE colocarse en el <head> ANTES de @vite y @livewireStyles.
       Al ser síncrono, bloquea el render hasta aplicar la clase .dark —
       eso es intencional: previene el flash de tema incorrecto (FOUT).

    ❌ NO usa localStorage — la DB es la única fuente de verdad.
    ❌ NO tiene lógica Alpine — no necesita reactividad en runtime.
    ✅ El tema solo cambia desde Perfil → Preferencias de Interfaz.
--}}
<script>
    (function () {
        var theme = @json($theme);
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var isDark = theme === 'dark' || (theme === 'system' && prefersDark);
        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>