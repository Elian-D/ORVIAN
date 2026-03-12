<?php

namespace App\Livewire\Geographic;

use App\Models\Geo\Province;
use App\Models\Geo\Municipality;
use App\Models\Geo\District;
use App\Models\Geo\Section;
use App\Models\Geo\Neighborhood;
use Livewire\Component;

class GeographicSelects extends Component
{
    // Listas para los dropdowns
    public $provinces, $municipalities = [], $districts = [], $sections = [], $neighborhoods = [];

    // Valores seleccionados (wire:model)
    public $province_id, $municipality_id, $district_id, $section_id, $neighborhood_id;

    public function mount()
    {
        $this->provinces = Province::orderBy('name')->get();
    }

    // --- HOOKS DE ACTUALIZACIÓN ---

    public function updatedProvinceId($value)
    {
        $this->municipalities = $value ? Municipality::where('province_id', $value)->orderBy('name')->get() : [];
        $this->reset(['municipality_id', 'district_id', 'section_id', 'neighborhood_id', 'districts', 'sections', 'neighborhoods']);
    }

    public function updatedMunicipalityId($value)
    {
        $this->districts = $value ? District::where('municipality_id', $value)->orderBy('name')->get() : [];
        $this->reset(['district_id', 'section_id', 'neighborhood_id', 'sections', 'neighborhoods']);
    }

    public function updatedDistrictId($value)
    {
        $this->sections = $value ? Section::where('district_id', $value)->orderBy('name')->get() : [];
        $this->reset(['section_id', 'neighborhood_id', 'neighborhoods']);
    }

    public function updatedSectionId($value)
    {
        $this->neighborhoods = $value ? Neighborhood::where('section_id', $value)->orderBy('name')->get() : [];
        $this->reset('neighborhood_id');
    }

    public function updatedNeighborhoodId($value)
    {
        // Emitimos un evento para que el componente padre (Wizard) reciba la ubicación final
        $this->dispatch('locationSelected', [
            'neighborhood_id' => $value,
            'section_id' => $this->section_id,
            // ... otros datos si los necesitas
        ]);
    }

    public function render()
    {
        return view('livewire.geographic.geographic-selects');
    }
}