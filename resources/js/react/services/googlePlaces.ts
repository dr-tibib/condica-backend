import { debounce } from 'lodash';
import haversine from 'haversine-distance';
import useAppStore from '../store/appStore';

// Removed deprecated AutocompleteService
// const autocompleteService = new google.maps.places.AutocompleteService();
// We keep PlacesService for getDetails for now, though it should be migrated to Place.fetchFields eventually.
const placesService = new google.maps.places.PlacesService(document.createElement('div'));

const searchPlaces = async (query: string, callback: (results: google.maps.places.AutocompletePrediction[]) => void) => {
    if (!query) {
        callback([]);
        return;
    }

    try {
        // Use the new AutocompleteSuggestion API
        // @ts-ignore
        const { AutocompleteSuggestion } = await google.maps.importLibrary("places");

        // @ts-ignore
        const response = await AutocompleteSuggestion.fetchAutocompleteSuggestions({ input: query });

        // Map to legacy AutocompletePrediction format for backward compatibility
        const predictions = (response.suggestions || []).map((suggestion: any) => {
             const pred = suggestion.placePrediction;
             if (!pred) return null;

             return {
                 description: pred.text.text,
                 place_id: pred.placeId,
                 structured_formatting: {
                     main_text: pred.mainText ? pred.mainText.text : pred.text.text,
                     secondary_text: pred.secondaryText ? pred.secondaryText.text : '',
                     main_text_matched_substrings: [],
                 },
                 types: pred.types || [],
                 matched_substrings: [],
                 terms: [],
             };
        }).filter((p: any) => p !== null) as google.maps.places.AutocompletePrediction[];

        callback(predictions);
    } catch (e) {
        console.error("Error fetching suggestions:", e);
        callback([]);
    }
};

export const debouncedSearchPlaces = debounce(searchPlaces, 300);

export const getPlaceDetails = (placeId: string): Promise<google.maps.places.PlaceResult> => {
    return new Promise((resolve, reject) => {
        placesService.getDetails({ placeId, fields: ['geometry.location'] }, (result, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && result) {
                resolve(result);
            } else {
                reject(new Error('Failed to get place details.'));
            }
        });
    });
};

export const calculateDistance = (place: google.maps.places.PlaceResult) => {
    const workplaceLocation = useAppStore.getState().settings.workplace_location;
    if (workplaceLocation && place.geometry?.location) {
        const distanceInMeters = haversine(
            { latitude: workplaceLocation.lat, longitude: workplaceLocation.lng },
            { latitude: place.geometry.location.lat(), longitude: place.geometry.location.lng() }
        );
        return (distanceInMeters * 0.000621371).toFixed(1); // Convert to miles
    }
    return null;
};
