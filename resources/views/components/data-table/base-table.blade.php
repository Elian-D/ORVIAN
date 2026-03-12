@props([
    'items', 
    'definition',
    'visibleColumns' => []
])

<div class="flex flex-col w-full" x-data="{ showSettings: false }">
    <div class="flex justify-between items-center mb-4 px-2">
        <div class="flex-1">
            {{ $filters ?? '' }}
        </div>
        
        <div class="relative ml-4">
            <button @click="showSettings = !showSettings" class="p-2 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
            </button>

            <div x-show="showSettings" @click.away="showSettings = false" class="absolute right-0 z-50 mt-2 w-56 bg-white border rounded-xl shadow-xl p-4">
                <h3 class="text-xs font-bold uppercase text-gray-400 mb-2">Columnas Visibles</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                    @foreach($definition::allColumns() as $key => $label)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" wire:click="toggleColumn('{{ $key }}')" 
                                   @checked(in_array($key, $visibleColumns))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="w-full overflow-x-auto border border-gray-200 rounded-xl bg-white shadow-sm custom-scrollbar">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($definition::allColumns() as $key => $label)
                        @if(in_array($key, $visibleColumns))
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-widest 
                                {{ in_array($key, $definition::defaultMobile()) ? 'table-cell' : 'hidden md:table-cell' }}">
                                {{ $label }}
                            </th>
                        @endif
                    @endforeach
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $item)
                    {{ $slot }}
                @empty
                    <tr>
                        {{-- El colspan suma las columnas visibles + 1 de la columna de acciones --}}
                        <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                            <x-ui.empty-state 
                                variant="simple" 
                                title="Búsqueda sin resultados"
                                description="No encontramos ningún registro que coincida con los filtros aplicados actualmente."
                                {{-- Si quieres que aparezca el botón de crear cuando está vacía --}}
                                :actionLabel="isset($actionLabel) ? $actionLabel : null"
                                :actionClick="isset($actionClick) ? $actionClick : null"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>