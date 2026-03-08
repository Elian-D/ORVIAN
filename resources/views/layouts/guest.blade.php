<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">

    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ORVIAN') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Orbs exteriores */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
        }

        /* Fade-in-up para el card */
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Orbs del panel izquierdo */
        .panel-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        /* Input underline */
        .input-underline {
            background: transparent;
            border: none;
            border-bottom: 1.5px solid;
            border-radius: 0;
            outline: none;
            transition: border-color 0.2s, color 0.2s;
            width: 100%;
            padding: 10px 0 10px 36px;
            font-size: 0.9rem;
        }
        .input-underline::placeholder {
            transition: color 0.2s;
        }

        /* Toggle iOS */
        .ios-toggle {
            position: relative;
            width: 52px;
            height: 28px;
            border-radius: 999px;
            transition: background 0.3s;
            cursor: pointer;
            flex-shrink: 0;
        }
        .ios-toggle-thumb {
            position: absolute;
            top: 3px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
            transition: left 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Orbit rings decorativos */
        .orbit-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.07);
            pointer-events: none;
        }
    </style>
</head>
<body class="font-sans antialiased transition-colors duration-500 min-h-screen flex items-center justify-center p-4 sm:p-8 relative overflow-hidden"
      style="background: #030a18;">

    {{-- Orbs de fondo exterior --}}
    <div class="bg-orb" style="width:500px;height:500px;background:#04275f;opacity:0.5;top:-100px;left:-120px;"></div>
    <div class="bg-orb" style="width:420px;height:420px;background:#f78904;opacity:0.25;bottom:-80px;right:-80px;"></div>
    <div class="bg-orb" style="width:280px;height:280px;background:#1e3a6e;opacity:0.4;top:60%;right:5%;"></div>
    
    {{-- Esferas flotantes decorativas --}}
    <div class="fixed" style="top:5%;right:8%;width:56px;height:56px;border-radius:50%;background:radial-gradient(circle at 35% 35%,#f78904,#c96a00);box-shadow:0 8px 32px rgba(247,137,4,0.4);pointer-events:none;z-index:1;"></div>
    <div class="fixed" style="bottom:8%;left:6%;width:80px;height:80px;border-radius:50%;background:radial-gradient(circle at 35% 35%,#3b6fd4,#1a3a8a);box-shadow:0 8px 32px rgba(59,111,212,0.4);pointer-events:none;z-index:1;"></div>
    <div class="fixed" style="top:55%;right:2%;width:44px;height:44px;border-radius:50%;background:radial-gradient(circle at 35% 35%,#5b8dee,#2a50a8);box-shadow:0 6px 24px rgba(91,141,238,0.4);pointer-events:none;z-index:1;"></div>

    <div class="pointer-events-none absolute top-4 right-4 z-50 flex flex-col gap-3 w-full max-w-sm px-4 md:px-0">
        <div class="pointer-events-auto">
            <x-ui.toasts />
        </div>
    </div>

    {{-- Card principal --}}
    <div class="w-full max-w-5xl rounded-[2rem] overflow-hidden flex flex-col lg:flex-row relative z-10 animate-fade-in-up"
         style="min-height:560px;box-shadow:0 40px 80px -20px rgba(0,0,0,0.6),0 0 0 1px rgba(255,255,255,0.06);">

        {{-- Panel izquierdo: Branding --}}
        <div class="hidden lg:flex lg:w-[48%] flex-col justify-between relative overflow-hidden p-12"
             style="background: #04275f;">

            {{-- Orbit rings --}}
            <div class="orbit-ring" style="width:500px;height:500px;top:50%;left:50%;transform:translate(-50%,-50%);"></div>
            <div class="orbit-ring" style="width:350px;height:350px;top:50%;left:50%;transform:translate(-50%,-50%);"></div>

            {{-- Orbs del panel --}}
            <div class="panel-orb" style="width:300px;height:300px;background:#0d3a7a;opacity:0.8;top:-60px;left:-60px;"></div>
            <div class="panel-orb" style="width:250px;height:250px;background:#f78904;opacity:0.2;bottom:-40px;right:-40px;"></div>

            {{-- Contenido top --}}
            <div class="relative z-10">
                <x-application-logo type="full" mode="dark" class="h-13" />
            </div>

            {{-- Contenido central --}}
            <div class="relative z-10">
                <h2 class="text-4xl xl:text-5xl font-black text-white leading-[1.1] mb-6">
                    Gestión educativa<br>
                    <span style="color:#f78904;">inteligente y fluida.</span>
                </h2>
                <p class="text-base text-blue-100/65 leading-relaxed max-w-sm">
                    Bienvenido al ecosistema modular diseñado para transformar la administración escolar en la República Dominicana.
                </p>
            </div>

            {{-- Badge inferior --}}
            <div class="relative z-10">
                <div class="inline-flex items-center gap-4 px-5 py-3.5 rounded-2xl"
                     style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);">
                    <div class="flex -space-x-2.5">
                        <div class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-[10px] font-black text-white"
                             style="border-color:#04275f;background:#f78904;box-shadow:0 4px 12px rgba(247,137,4,0.35);">OR</div>
                        <div class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-[10px] font-black text-white"
                             style="border-color:#04275f;background:#3b6fd4;">VI</div>
                        <div class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-[10px] font-black text-white"
                             style="border-color:#04275f;background:#5b4ecf;">AN</div>
                    </div>
                    <div>
                        <p class="text-white text-sm font-bold leading-none mb-1">ORVIAN System</p>
                        <p class="text-blue-100/50 text-[10px] uppercase tracking-widest font-bold">Versión 0.1.0 Alpha</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel derecho: Formulario --}}
        <div class="flex-1 flex flex-col justify-between relative"
             :class="darkMode ? 'bg-[#0d1424]' : 'bg-white'">

            {{-- Formulario centrado --}}
            <div class="flex-1 flex items-center justify-center px-8 sm:px-14 py-12">
                <div class="w-full max-w-sm">

                    {{-- Logo móvil --}}
                    <div class="lg:hidden flex justify-center mb-10">
                        <x-application-logo type="full" mode="dynamic" class="h-10" />
                    </div>

                    {{ $slot }}
                </div>
            </div>

            {{-- Footer del panel --}}
            <div class="px-8 sm:px-14 py-5 flex items-center justify-between"
                 :class="darkMode ? 'border-t border-white/5' : 'border-t border-gray-100'">

                <div class="flex items-center gap-4">
                    <a href="#" class="text-xs transition-colors"
                       :class="darkMode ? 'text-white/30 hover:text-white/60' : 'text-gray-400 hover:text-gray-600'">
                        Términos y Condiciones
                    </a>
                    <span :class="darkMode ? 'text-white/15' : 'text-gray-200'">|</span>
                    <a href="#" class="text-xs transition-colors"
                       :class="darkMode ? 'text-white/30 hover:text-white/60' : 'text-gray-400 hover:text-gray-600'">
                        Política de Privacidad
                    </a>
                </div>

                {{-- Toggle iOS --}}
                <button @click="darkMode = !darkMode" 
                        class="ios-toggle flex items-center"
                        :style="darkMode ? 'background:#1e3a5f;' : 'background:#e5e7eb;'">
                    <div class="ios-toggle-thumb"
                         :style="darkMode ? 'left:27px;' : 'left:3px;'">
                        {{-- Ícono sol --}}
                        <svg x-show="!darkMode" class="w-3 h-3" style="color:#f78904;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                        </svg>
                        {{-- Ícono luna --}}
                        <svg x-show="darkMode" class="w-3 h-3" style="color:#3b6fd4;" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

    </div>
</body>
</html>