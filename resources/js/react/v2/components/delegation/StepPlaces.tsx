import React, { useState, useEffect } from 'react';
import axios from 'axios';
import GooglePlacesAutocomplete from 'react-google-places-autocomplete';

interface Place {
  id?: number;
  google_place_id: string;
  name: string;
  address?: string;
  photo_reference?: string;
  latitude?: number;
  longitude?: number;
}

interface StepPlacesProps {
  selectedPlaces: Place[];
  onSelectionChange: (places: Place[]) => void;
  onNext: () => void;
  onBack: () => void;
}

const StepPlaces = ({ selectedPlaces, onSelectionChange, onNext, onBack }: StepPlacesProps) => {
  const [savedPlaces, setSavedPlaces] = useState<Place[]>([]);
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

  useEffect(() => {
    fetchSavedPlaces();
  }, []);

  const fetchSavedPlaces = async () => {
    try {
      const response = await axios.get('/api/kiosk/saved-places');
      setSavedPlaces(response.data.data);
    } catch (error) {
      console.error('Failed to fetch places', error);
    }
  };

  const togglePlace = (place: Place) => {
    const exists = selectedPlaces.find(p => p.google_place_id === place.google_place_id);
    if (exists) {
      onSelectionChange(selectedPlaces.filter(p => p.google_place_id !== place.google_place_id));
    } else {
      onSelectionChange([...selectedPlaces, place]);
    }
  };

  const handleGoogleSelect = (val: any) => {
      if (!val) return;

      const service = new google.maps.places.PlacesService(document.createElement('div'));
      service.getDetails({ placeId: val.value.place_id, fields: ['name', 'formatted_address', 'geometry', 'photos'] }, (place, status) => {
          if (status === google.maps.places.PlacesServiceStatus.OK && place) {
              // Try to get a photo URL or reference
              let photoRef = undefined;
              if (place.photos && place.photos.length > 0) {
                 photoRef = place.photos[0].getUrl({ maxWidth: 400 }); 
              }

              const newPlace: Place = {
                  google_place_id: val.value.place_id,
                  name: place.name || val.label,
                  address: place.formatted_address,
                  latitude: place.geometry?.location?.lat(),
                  longitude: place.geometry?.location?.lng(),
                  photo_reference: photoRef
              };
              
              // Add to selected if not exists
               const exists = selectedPlaces.find(p => p.google_place_id === newPlace.google_place_id);
               if (!exists) {
                  onSelectionChange([...selectedPlaces, newPlace]);
               }
          }
      });
  };

  return (
    <div className="flex flex-col h-full bg-white dark:bg-slate-800 rounded-3xl shadow-2xl overflow-hidden border border-slate-300 dark:border-slate-700 w-full max-w-5xl mx-auto my-auto relative">
      <div className="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
        <h1 className="text-3xl font-extrabold text-slate-800 dark:text-white uppercase tracking-tight">Configurare Delegație</h1>
        <button onClick={onBack} className="w-12 h-12 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 active:scale-90 transition-transform">
          <span className="material-symbols-outlined">close</span>
        </button>
      </div>

      <div className="flex-grow p-8 flex flex-col gap-8 overflow-y-auto scroll-hide">
         <div className="space-y-3">
            <label className="text-sm font-bold text-slate-500 uppercase tracking-widest ml-1">Caută Destinație</label>
            <div className="relative text-black">
                <GooglePlacesAutocomplete
                    apiKey={apiKey}
                    selectProps={{
                        placeholder: 'Introduceți orașul sau firma...',
                        onChange: handleGoogleSelect,
                        styles: {
                            input: (provided) => ({ ...provided, padding: '10px', fontSize: '1.25rem' }),
                            control: (provided) => ({ ...provided, borderRadius: '1rem', padding: '5px' }),
                        }
                    }}
                />
            </div>
         </div>

         <div className="space-y-4">
            <label className="text-sm font-bold text-slate-500 uppercase tracking-widest ml-1">Destinații Recurente</label>
            <div className="grid grid-cols-2 gap-4">
                {savedPlaces.map(place => {
                    const isSelected = selectedPlaces.some(p => p.google_place_id === place.google_place_id);
                    return (
                        <button 
                            key={place.google_place_id}
                            onClick={() => togglePlace(place)}
                            className={`flex items-center gap-4 p-4 rounded-2xl border-2 transition-all text-left active:scale-[0.98] ${isSelected ? 'border-primary bg-blue-50 dark:bg-blue-900/20 text-primary dark:text-blue-400' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300'}`}
                        >
                            {place.photo_reference ? (
                                <img src={place.photo_reference.startsWith('http') ? place.photo_reference : `https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photo_reference=${place.photo_reference}&key=${apiKey}`} alt={place.name} className="w-20 h-20 rounded-xl object-cover shadow-sm" />
                            ) : (
                                <div className="w-20 h-20 rounded-xl bg-slate-200 flex items-center justify-center">
                                    <span className="material-symbols-outlined text-3xl">location_city</span>
                                </div>
                            )}
                            <div className="flex-grow">
                                <span className="text-xl font-bold block">{place.name}</span>
                                <span className="text-sm opacity-70 truncate">{place.address}</span>
                            </div>
                            <span className={`material-symbols-outlined text-4xl ${isSelected ? '' : 'opacity-20'}`}>
                                {isSelected ? 'check_circle' : 'radio_button_unchecked'}
                            </span>
                        </button>
                    );
                })}
            </div>
         </div>
      </div>

      <div className="p-8 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700">
        <button 
            onClick={onNext}
            disabled={selectedPlaces.length === 0}
            className="w-full bg-success hover:bg-green-700 text-white py-8 rounded-2xl text-4xl font-black shadow-xl shadow-green-500/20 flex items-center justify-center gap-4 active:scale-[0.98] transition-all uppercase tracking-tight disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span className="material-symbols-outlined text-5xl">directions_car</span>
            SELECTEAZĂ MAȘINA
        </button>
      </div>
    </div>
  );
};

export default StepPlaces;
