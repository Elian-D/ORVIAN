<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- SEO PRIMARIO                                               --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <title>ORVIAN — Sistema de Gestión Educativa para República Dominicana</title>
    <meta name="description"
          content="ORVIAN es el sistema integral de gestión educativa en la nube para instituciones dominicanas. Control de asistencia biométrica, notas, comunicaciones y administración escolar en un solo ecosistema." />
    <meta name="keywords"
          content="asistencia automática, asistencia biométrica, sistema de notas, notas sistema dominicana, sistema gestión centro en la nube, software educativo República Dominicana, gestión escolar RD, MINERD, plataforma educativa dominicana" />
    <meta name="robots" content="index, follow" />
    <meta name="author" content="ORVIAN" />
    <meta name="geo.region" content="DO" />
    <meta name="geo.placename" content="República Dominicana" />
    <link rel="canonical" href="https://orvian.com.do" />

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- OPEN GRAPH (Facebook, LinkedIn, WhatsApp, etc.)           --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <meta property="og:type"        content="website" />
    <meta property="og:site_name"   content="ORVIAN" />
    <meta property="og:locale"      content="es_DO" />
    <meta property="og:url"         content="https://orvian.com.do" />
    <meta property="og:title"       content="ORVIAN — Sistema de Gestión Educativa para República Dominicana" />
    <meta property="og:description" content="Control de asistencia biométrica, notas, comunicaciones y administración escolar en un solo ecosistema. Diseñado para instituciones dominicanas." />
    <meta property="og:image"       content="{{ asset('img/og-image.jpg') }}" />
    <meta property="og:image:width"  content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:image:alt"    content="ORVIAN — Sistema de Gestión Educativa" />

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- TWITTER CARDS                                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <meta name="twitter:card"        content="summary_large_image" />
    <meta name="twitter:site"        content="@orvian_do" />
    <meta name="twitter:title"       content="ORVIAN — Sistema de Gestión Educativa para República Dominicana" />
    <meta name="twitter:description" content="Control de asistencia biométrica, notas, comunicaciones y administración escolar en un solo ecosistema." />
    <meta name="twitter:image"       content="{{ asset('img/og-image.jpeg') }}" />
    <meta name="twitter:image:alt"   content="ORVIAN — Sistema de Gestión Educativa" />

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- FAVICON POR TEMA                                          --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-light.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: light)">
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-dark.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: dark)">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- JSON-LD — STRUCTURED DATA (SoftwareApplication)          --}}
    {{-- Google usa esto para mostrar información rica             --}}
    {{-- en los resultados de búsqueda (Rich Results).            --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @verbatim
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "ORVIAN",
        "url": "https://orvian.com.do",
        "logo": "https://orvian.com.do/img/logos/logo-icon-light.svg",
        "description": "...",
        "applicationCategory": "EducationApplication",
        "operatingSystem": "All",
    @endverbatim
        "softwareVersion": "{{ $appVersion ?? '1.0' }}",
    @verbatim
        "inLanguage": "es-DO",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "DOP",
            "priceSpecification": {
                "@type": "UnitPriceSpecification",
                "price": "0",
                "priceCurrency": "DOP",
                "description": "Precio bajo consulta"
            },
            "availability": "https://schema.org/InStock"
        },
        "publisher": {
            "@type": "Organization",
            "name": "ORVIAN",
            "url": "https://orvian.com.do",
            "logo": "https://orvian.com.do/img/logos/logo-icon-light.svg",
            "contactPoint": {
                "@type": "ContactPoint",
                "contactType": "customer support",
                "availableLanguage": "Spanish",
                "areaServed": "DO"
            }
        },
        "featureList": [
            "Control de asistencia biométrica",
            "Registro de notas y calificaciones",
            "Boletines académicos en PDF",
            "Comunicaciones vía WhatsApp",
            "Gestión multi-tenant por centro",
            "Dashboard de analítica en tiempo real"
        ],
        "screenshot": "https://orvian.com.do/img/og-image.jpeg",
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.9",
            "ratingCount": "47",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- TEMA: script síncrono antes del CSS para evitar flash     --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <x-ui.theme-init />

    {{-- Estilos --}}
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-dark-bg text-slate-800 dark:text-slate-100 antialiased 
             selection:bg-state-info/30 selection:text-orvian-navy 
             dark:selection:bg-orvian-orange/20 dark:selection:text-orvian-orange custom-scroll">

    {{-- Toasts globales --}}
    <x-ui.toasts />

    <div class="flex min-h-screen flex-col">
        @include('layouts.navigation')

        <main id="main-content" class="flex-grow">
            {{ $slot }}
        </main>

        @include('layouts.footer')
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BOTÓN FLOTANTE DE WHATSAPP                                --}}
    {{-- Aparece tras 400px de scroll. Pulso sutil para atención. --}}
    {{-- El número +1 829 625 7463 ya está en el CTA de la        --}}
    {{-- landing; lo centralizamos aquí para consistencia.        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div
        x-data="{ visible: false }"
        @scroll.window="visible = window.scrollY > 400"
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-90"
        class="fixed bottom-6 right-6 z-50"
        aria-label="Contactar por WhatsApp"
    >
        {{-- Anillo de pulso (decorativo, pointer-events-none para no bloquear el botón) --}}
        <span class="absolute inset-0 rounded-full bg-[#25D366] opacity-30 animate-ping pointer-events-none"></span>

        <a
            href="https://wa.me/18296257463?text=Hola%2C%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20ORVIAN."
            target="_blank"
            rel="noopener noreferrer"
            title="Contactar con ORVIAN por WhatsApp"
            class="relative flex items-center justify-center w-14 h-14 rounded-full
                   bg-[#25D366] hover:bg-[#20BA5A]
                   shadow-lg shadow-[#25D366]/40
                   transition-all duration-300
                   hover:scale-110 hover:shadow-xl hover:shadow-[#25D366]/50
                   focus:outline-none focus:ring-4 focus:ring-[#25D366]/40"
            aria-label="Abrir chat de WhatsApp con ORVIAN"
        >
            {{-- Ícono SVG oficial de WhatsApp --}}
            <svg class="w-7 h-7 text-white fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
    </div>

    {{-- Scripts --}}
    @livewireScripts
    @stack('scripts')
</body>
</html>