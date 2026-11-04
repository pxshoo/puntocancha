let mapsPromise;

export function loadGoogleMaps(apiKey) {
    if (!apiKey) return Promise.reject(new Error('missing-key'));
    if (window.google?.maps?.importLibrary) return Promise.resolve(window.google.maps);
    if (mapsPromise) return mapsPromise;
    mapsPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        const timer = setTimeout(() => fail(), 15000);
        const fail = () => {
            clearTimeout(timer); script.remove(); mapsPromise = null;
            delete window.puntoCanchaGoogleMapsReady;
            reject(new Error('maps-unavailable'));
        };
        window.puntoCanchaGoogleMapsReady = () => {
            clearTimeout(timer); delete window.puntoCanchaGoogleMapsReady; resolve(window.google.maps);
        };
        script.src = 'https://maps.googleapis.com/maps/api/js?' + new URLSearchParams({
            key: apiKey, loading: 'async', callback: 'puntoCanchaGoogleMapsReady',
            v: 'quarterly', language: 'es', region: 'CL',
        });
        script.async = true;
        script.onerror = fail;
        document.head.append(script);
    });
    return mapsPromise;
}

export function googleAddress(result) {
    const components = result?.address_components ?? [];
    const part = type => components.find(component => component.types.includes(type))?.long_name ?? '';
    return {
        address: [part('route'), part('street_number')].filter(Boolean).join(' '),
        city: part('locality') || part('administrative_area_level_3') || part('administrative_area_level_2'),
    };
}

export default (address = '', city = '', latitude = null, longitude = null) => {
    let map, marker, geocoder, maps, AdvancedMarkerElement;
    return {
        locationAddress: address ?? '', locationCity: city ?? '',
        latitude: latitude ?? '', longitude: longitude ?? '',
        resolving: false, loadingMap: false, locationNotice: '', mapOpen: false, mapError: '', locating: false,
        get hasPoint() { return this.latitude !== '' && this.longitude !== '' && this.latitude !== null && this.longitude !== null; },
        get locationReady() { return this.locationAddress.trim().length > 0 && this.locationCity.trim().length > 0; },
        get locationQuery() { return [this.locationAddress.trim(), this.locationCity.trim(), 'Chile'].filter(Boolean).join(', '); },
        get locationMapUrl() {
            return this.hasPoint || this.locationReady ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(this.hasPoint ? this.latitude + ',' + this.longitude : this.locationQuery) : null;
        },
        syncLocation(event) {
            if (event.target.name === 'address') this.locationAddress = event.target.value;
            if (event.target.name === 'city') this.locationCity = event.target.value;
        },
        async openMap() {
            this.mapOpen = true; this.mapError = ''; this.loadingMap = true;
            try {
                maps = await loadGoogleMaps(this.$refs.openMap.dataset.googleMapsKey);
                const [mapLibrary, markerLibrary, geocodingLibrary] = await Promise.all([
                    maps.importLibrary('maps'), maps.importLibrary('marker'), maps.importLibrary('geocoding'),
                ]);
                AdvancedMarkerElement = markerLibrary.AdvancedMarkerElement;
                geocoder ??= new geocodingLibrary.Geocoder();
                await this.$nextTick();
                if (!this.mapOpen) return;
                if (!map) {
                    map = new mapLibrary.Map(this.$refs.mapCanvas, {
                        center: this.hasPoint ? this.point() : { lat: -35.4, lng: -71.6 },
                        zoom: this.hasPoint ? 16 : 5,
                        mapId: this.$refs.openMap.dataset.googleMapsId || 'DEMO_MAP_ID',
                        streetViewControl: false, mapTypeControl: false, fullscreenControl: false,
                    });
                    map.addListener('click', event => {
                        if (event.latLng) this.selectPoint({ latitude: event.latLng.lat(), longitude: event.latLng.lng() });
                    });
                }
                if (this.hasPoint) { this.updateMarker(); map.setCenter(this.point()); }
                else if (this.locationReady) await this.searchAddress();
            } catch (error) {
                this.mapError = error.message === 'missing-key'
                    ? 'Google Maps aún no está configurado. Agrega GOOGLE_MAPS_API_KEY en la configuración de la aplicación.'
                    : 'No pudimos cargar Google Maps. Revisa la conexión y la configuración de la API.';
            } finally { this.loadingMap = false; }
        },
        point() { return { lat: Number(this.latitude), lng: Number(this.longitude) }; },
        updateMarker() {
            if (!map || !AdvancedMarkerElement || !this.hasPoint) return;
            if (!marker) {
                marker = new AdvancedMarkerElement({ map, position: this.point(), gmpDraggable: true, title: 'Ubicación del club' });
                marker.addListener('dragend', event => {
                    if (event.latLng) this.selectPoint({ latitude: event.latLng.lat(), longitude: event.latLng.lng() });
                });
            } else { marker.map = map; marker.position = this.point(); }
        },
        selectPoint(point) {
            this.latitude = Number(point.latitude).toFixed(7);
            this.longitude = Number(point.longitude).toFixed(7);
            this.updateMarker();
        },
        clearPoint() { this.latitude = ''; this.longitude = ''; if (marker) marker.map = null; },
        async searchAddress() {
            if (!geocoder || !this.locationReady) return;
            try {
                const { results } = await geocoder.geocode({ address: this.locationQuery, region: 'cl' });
                if (results[0]) { map.setCenter(results[0].geometry.location); map.setZoom(16); }
            } catch { this.mapError = 'No encontramos esa dirección. Puedes marcar el punto manualmente.'; }
        },
        async applyPoint() {
            if (!this.hasPoint || this.resolving) return;
            this.resolving = true; this.locationNotice = '';
            try {
                if (!geocoder) throw new Error('not-ready');
                const { results } = await geocoder.geocode({ location: this.point() });
                const result = googleAddress(results.find(item => item.address_components?.some(component => component.types.includes('route'))) ?? results[0]);
                if (result.address) this.locationAddress = result.address;
                if (result.city) this.locationCity = result.city;
                this.locationNotice = result.address && result.city
                    ? 'Dirección actualizada desde Google Maps. Revísala y guarda los cambios.'
                    : 'Punto seleccionado. Revisa y completa la dirección antes de guardar.';
            } catch {
                this.locationNotice = 'Punto seleccionado. No pudimos consultar la dirección; escríbela manualmente y guarda los cambios.';
            } finally { this.resolving = false; this.closeMap(); }
        },
        trapMapFocus(event) {
            const elements = [...event.currentTarget.querySelectorAll('button:not([disabled]), a[href], [tabindex="0"]')];
            const first = elements[0], last = elements[elements.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        },
        closeMap() { this.mapOpen = false; this.$nextTick(() => this.$refs.openMap.focus()); },
        locateMe() {
            if (!navigator.geolocation || !map) { this.mapError = 'Carga el mapa para usar tu ubicación.'; return; }
            this.locating = true;
            navigator.geolocation.getCurrentPosition(position => {
                this.selectPoint(position.coords); map.setCenter(this.point()); map.setZoom(16); this.locating = false;
            }, () => { this.locating = false; this.mapError = 'No pudimos obtener tu ubicación. Marca el punto directamente en el mapa.'; }, { timeout: 10000 });
        },
        destroy() { if (map && maps) maps.event.clearInstanceListeners(map); if (marker) { maps.event.clearInstanceListeners(marker); marker.map = null; } },
    };
};
