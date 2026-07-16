<?php

namespace App\Services\Navigation;

use App\Models\User;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Índice del buscador global de escuela (REQ-07.4, rediseñado).
 *
 * Fuente: metadatos `->defaults('navigationSearch', [...])` puestos
 * directamente en routes/app/*.php (no config('modules.php') — ese archivo
 * describe la ESTRUCTURA del Sidebar por módulo, "Académico > Estudiantes";
 * el buscador necesita ACCIONES ("Crear Estudiante", "Registrar Excusa") que
 * no tienen cabida ahí, y son justo lo que un usuario nuevo escribe cuando
 * no conoce el nombre del módulo).
 *
 * OJO con el nombre de la clave: `->defaults()` inyecta un parámetro de ruta,
 * y Livewire vincula automáticamente parámetros de ruta a propiedades
 * públicas del mismo nombre. Usar 'search' rompió en producción varias
 * páginas que ya tenían `public string $search` para su propio buscador
 * local (BiometricKiosk, EnrollmentHub, StudentPrintManager) — Livewire
 * intentaba asignarles el array de metadatos entero. Por eso 'navigationSearch',
 * no 'search' — cualquier clave nueva debe verificarse contra nombres de
 * propiedad públicos comunes antes de usarse aquí.
 *
 * Dos capas:
 *   - index()   → escaneo de Route::getRoutes(), cacheado 24h. Caro (recorre
 *                 todas las rutas de la app), por eso se cachea. Datos "crudos"
 *                 sin filtrar por permisos — el caché es el mismo para todos
 *                 los usuarios.
 *   - forUser() → filtra index() contra los permisos del usuario autenticado.
 *                 NO se cachea: son ~25-40 items, filtrar es barato, y cachear
 *                 por usuario/rol sería una capa de invalidación innecesaria
 *                 para el tamaño de datos real de este sistema.
 */
class GlobalSearchService
{
    public const CACHE_KEY = 'nav.search-index.routes';

    /**
     * @return array<int, array{name: string, url: string, title: string, description: ?string, keywords: array, icon: string, permissions: array}>
     */
    public static function index(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            return collect(Route::getRoutes())
                ->filter(fn (RouteInstance $route) => isset($route->defaults['navigationSearch'])
                    && str_starts_with((string) $route->getName(), 'app.'))
                ->map(function (RouteInstance $route) {
                    $meta = $route->defaults['navigationSearch'];
                    $name = $route->getName();

                    return [
                        'name'        => $name,
                        'url'         => route($name),
                        'title'       => $meta['title'],
                        'description' => $meta['description'] ?? null,
                        'keywords'    => $meta['keywords'] ?? [],
                        'icon'        => self::iconFor($name),
                        'permissions' => self::permissionsFor($route),
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * index() filtrado por lo que el usuario autenticado puede ver.
     * Devuelve [] si no hay usuario o no tiene escuela (mismo alcance que
     * el resto del buscador — ver components/navbar/layout.blade.php).
     */
    public static function forUser(?User $user): array
    {
        if (!$user || !$user->school_id) {
            return [];
        }

        return array_values(array_filter(
            self::index(),
            fn (array $item) => self::userCanSeeItem($user, $item)
        ));
    }

    private static function userCanSeeItem(User $user, array $item): bool
    {
        foreach ($item['permissions'] as $permission) {
            if (!$user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extrae los permisos de los middleware `can:` de la ruta. Solo el primer
     * argumento de cada `can:` cuenta como permiso — Laravel trata el resto
     * como argumentos extra del Gate (no como permisos adicionales), así que
     * `can:settings.view, settings.update` en runtime solo exige
     * `settings.view`. Varios middleware `can:` sí se exigen todos (AND).
     */
    private static function permissionsFor(RouteInstance $route): array
    {
        $permissions = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (str_starts_with($middleware, 'can:')) {
                $permissions[] = trim(explode(',', substr($middleware, 4))[0]);
            }
        }

        return $permissions;
    }

    private static function iconFor(string $routeName): string
    {
        return match (true) {
            str_starts_with($routeName, 'app.attendance.') => 'asistencia',
            str_starts_with($routeName, 'app.academic.')   => 'academico',
            default                                          => 'administracion',
        };
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
