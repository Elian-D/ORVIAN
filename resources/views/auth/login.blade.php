<x-guest-layout>

    <div class="text-center mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
            Bienvenido
        </h1>
        <p class="text-sm text-gray-500 dark:text-white/50 mt-2 font-medium">
            Ingresa a tu cuenta institucional
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }">
        @csrf

        {{-- Email Input --}}
        <div class="mb-5 group relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-white/30 group-focus-within:text-[#f78904] dark:group-focus-within:text-[#f78904] transition-colors pointer-events-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                placeholder="Correo electrónico" required autofocus
                class="w-full rounded-2xl pl-12 pr-4 py-4 text-sm font-medium bg-gray-50 dark:bg-[#0a101d] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-white/30 focus:border-[#f78904] focus:ring-1 focus:ring-[#f78904] dark:focus:border-[#f78904] outline-none transition-all shadow-sm"
            />
        </div>

        {{-- Password Input --}}
        <div class="mb-6 group relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-white/30 group-focus-within:text-[#f78904] dark:group-focus-within:text-[#f78904] transition-colors pointer-events-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </span>
            <input id="password" :type="showPassword ? 'text' : 'password'" name="password"
                placeholder="Contraseña" required
                class="w-full rounded-2xl pl-12 pr-12 py-4 text-sm font-medium bg-gray-50 dark:bg-[#0a101d] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-white/30 focus:border-[#f78904] focus:ring-1 focus:ring-[#f78904] dark:focus:border-[#f78904] outline-none transition-all shadow-sm"
            />
            <button type="button" @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-white/30 hover:text-[#f78904] dark:hover:text-[#f78904] transition-colors focus:outline-none">
                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg x-cloak x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </button>
        </div>

        {{-- Recordarme y Olvidó la clave --}}
        <div class="flex items-center justify-between mb-8 px-2">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 dark:border-white/20 text-[#f78904] focus:ring-[#f78904]/50 dark:bg-[#0a101d] transition-all">
                <span class="text-[13px] text-gray-500 dark:text-white/50 group-hover:text-[#f78904] transition-colors font-medium">Recordarme</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[13px] text-[#f78904] hover:underline hover:text-orange-500 font-semibold transition-colors">
                    ¿Olvidaste la clave?
                </a>
            @endif
        </div>

        {{-- Botón Submit --}}
        <button type="submit"
            class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#f78904] to-orange-500 text-white font-bold text-sm shadow-xl shadow-orange-500/20 hover:shadow-orange-500/40 hover:-translate-y-0.5 active:scale-95 transition-all">
            Iniciar Sesión
        </button>

    </form>
</x-guest-layout>