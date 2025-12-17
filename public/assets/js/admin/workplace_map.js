window.addEventListener('google_map:place_changed', function(e) {
    if (!e.detail || !e.detail.place) return;

    const place = e.detail.place;
    const components = place.address_components;
    
    if (!components) return;

    // Helper to get component value
    function getComponent(type, field = 'long_name') {
        const component = components.find(c => c.types.includes(type));
        return component ? component[field] : '';
    }

    // Mapping
    const streetNumber = getComponent('street_number');
    const route = getComponent('route');
    const city = getComponent('locality') || getComponent('postal_town') || getComponent('administrative_area_level_3');
    const county = getComponent('administrative_area_level_2');
    const country = getComponent('country');
    
    // Construct street address
    let address = '';
    if (route) address += route;
    if (streetNumber) address += (address ? ', ' : '') + streetNumber;

    // Update fields
    function updateField(name, value) {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
            input.value = value;
            // Trigger change event for any listeners
            input.dispatchEvent(new Event('change'));
            input.dispatchEvent(new Event('input'));
        }
    }

    updateField('city', city);
    updateField('county', county);
    updateField('country', country);
    updateField('street_address', address);

    console.log('Address fields updated from Google Map selection');
});
