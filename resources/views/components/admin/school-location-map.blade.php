@props(['lat', 'lng', 'name' => 'Ubicación', 'editable' => false])

<div 
    x-data="{
        map: null,
        marker: null,
        currentLat: @js($lat),
        currentLng: @js($lng),

        initMap() {
            const position = { 
                lat: parseFloat(this.currentLat) || 18.4861, 
                lng: parseFloat(this.currentLng) || -69.9312 
            };
            const isDark = document.documentElement.classList.contains('dark');
            
            const darkStyle = [
                { elementType: 'geometry', stylers: [{ color: '#111827' }] },
                { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#6b7280' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#111827' }] },
                { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#1f2937' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#374151' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#030712' }] }
            ];

            const setupGoogleMap = () => {
                this.map = new google.maps.Map(this.$refs.mapEl, {
                    zoom: 17,
                    center: position,
                    mapTypeId: 'hybrid',
                    styles: isDark ? darkStyle : [],
                    disableDefaultUI: true,
                    zoomControl: true,
                });

                this.marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: '{{ $name }}',
                    animation: google.maps.Animation.DROP,
                    draggable: {{ $editable ? 'true' : 'false' }},
                });

                if ({{ $editable ? 'true' : 'false' }}) {
                    this.marker.addListener('dragend', (event) => {
                        this.updateInternalCoords(event.latLng.lat(), event.latLng.lng());
                    });

                    this.map.addListener('click', (event) => {
                        const newPos = event.latLng;
                        this.marker.setPosition(newPos);
                        this.updateInternalCoords(newPos.lat(), newPos.lng());
                    });
                }
            };

            if (typeof google === 'undefined') {
                if (!document.getElementById('google-maps-script')) {
                    const script = document.createElement('script');
                    script.id = 'google-maps-script';
                    script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&callback=initGoogleMapsCb`;
                    script.async = true; 
                    script.defer = true;
                    document.head.appendChild(script);
                    window.initGoogleMapsCb = () => { 
                        window.dispatchEvent(new CustomEvent('google-maps-loaded'));
                    };
                }
                window.addEventListener('google-maps-loaded', setupGoogleMap);
            } else {
                setupGoogleMap();
            }
        },

        updateInternalCoords(lat, lng) {
            this.currentLat = lat;
            this.currentLng = lng;
            // Emitimos al componente Livewire para que guarde el estado temporal
            this.$dispatch('location-updated', { lat, lng });
        }
    }"
    x-init="initMap()"
    class="relative w-full h-full min-h-[15rem] rounded-3xl overflow-hidden border border-slate-200 dark:border-gray-800 z-10"
>
    <div x-ref="mapEl" class="w-full h-full bg-slate-100 dark:bg-gray-900"></div>
</div>