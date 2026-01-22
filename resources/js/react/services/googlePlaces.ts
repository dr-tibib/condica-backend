import { debounce } from 'lodash';
import haversine from 'haversine-distance';
import useAppStore from '../store/appStore';

const autocompleteService = new google.maps.places.AutocompleteService();
const placesService = new google.maps.places.PlacesService(document.createElement('div'));

const searchPlaces = (query: string, callback: (results: google.maps.places.AutocompletePrediction[]) => void) => {
    if (!query) {
        callback([]);
        return;
    }
    autocompleteService.getPlacePredictions({ input: query }, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && results) {
            callback(results);
        } else {
            callback([]);
        }
    });
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
