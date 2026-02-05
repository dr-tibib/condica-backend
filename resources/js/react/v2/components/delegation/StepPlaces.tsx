import React, { useState, useEffect, useMemo } from 'react';
import axios from 'axios';
import { APIProvider, useMapsLibrary } from '@vis.gl/react-google-maps';

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

const PlacesAutocomplete = ({
    savedPlaces,
    setSavedPlaces,
    onSelectPlace,
    inputValue,
    setInputValue
}: {
    savedPlaces: Place[],
    setSavedPlaces: (places: Place[]) => void,
    onSelectPlace: (place: Place) => void,
    inputValue: string,
    setInputValue: (val: string) => void
}) => {
    const placesLibrary = useMapsLibrary('places');
    const [predictions, setPredictions] = useState<google.maps.places.AutocompleteSuggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);

    useEffect(() => {
        // Fetch Google predictions
        if (placesLibrary && inputValue.length > 2) {
            // @ts-ignore
            placesLibrary.AutocompleteSuggestion.fetchAutocompleteSuggestions({ input: inputValue })
                .then((response: { suggestions: google.maps.places.AutocompleteSuggestion[] }) => {
                    setPredictions(response.suggestions || []);
                })
                .catch((e: unknown) => {
                    console.error(e);
                    setPredictions([]);
                });
        } else {
            setPredictions([]);
        }
    }, [inputValue, placesLibrary]);

    const handleSelectPrediction = async (suggestion: google.maps.places.AutocompleteSuggestion) => {
        if (!placesLibrary || !suggestion.placePrediction) return;

        try {
            const place = suggestion.placePrediction.toPlace();

            await place.fetchFields({
                fields: ['displayName', 'formattedAddress', 'location', 'photos'],
            });

            let photoRef = undefined;
            if (place.photos && place.photos.length > 0) {
                     // @ts-ignore
                    photoRef = place.photos[0].getURI ? place.photos[0].getURI({ maxWidth: 400 }) : undefined;
            }

            const newPlace: Place = {
                google_place_id: suggestion.placePrediction.placeId,
                name: place.displayName || suggestion.placePrediction.text.text,
                address: place.formattedAddress || (suggestion.placePrediction.secondaryText ? suggestion.placePrediction.secondaryText.text : ''),
                latitude: place.location?.lat(),
                longitude: place.location?.lng(),
                photo_reference: photoRef
            };

            // Add to saved places at the top
            const exists = savedPlaces.find(p => p.google_place_id === newPlace.google_place_id);
            if (!exists) {
                 setSavedPlaces([newPlace, ...savedPlaces]);
            }

            onSelectPlace(newPlace);
            setInputValue('');
            setShowSuggestions(false);
        } catch (error) {
            console.error('Error fetching place details:', error);
        }
    };

    return (
        <div className="relative">
             <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-3xl">search</span>
             <input
                className="w-full pl-14 pr-6 py-5 text-2xl rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:border-primary focus:ring-0 transition-all font-medium"
                placeholder="Introduceți orașul sau firma..."
                type="text"
                value={inputValue}
                onChange={(e) => {
                    setInputValue(e.target.value);
                    setShowSuggestions(true);
                }}
                onFocus={() => setShowSuggestions(true)}
                onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
             />

             {showSuggestions && predictions.length > 0 && (
                 <div className="absolute z-10 w-full mt-2 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 max-h-80 overflow-y-auto">
                     <div>
                         <div className="px-4 py-2 text-xs font-bold text-slate-400 uppercase">Google Places</div>
                         {predictions.map(suggestion => {
                             if (!suggestion.placePrediction) return null;
                             return (
                                 <div
                                    key={suggestion.placePrediction.placeId}
                                    className="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer flex items-center gap-3"
                                    onClick={() => handleSelectPrediction(suggestion)}
                                 >
                                     <span className="material-symbols-outlined text-slate-400">location_on</span>
                                     <div>
                                         <div className="font-bold text-slate-800 dark:text-slate-200">{suggestion.placePrediction.mainText ? suggestion.placePrediction.mainText.text : suggestion.placePrediction.text.text}</div>
                                         <div className="text-sm text-slate-500">{suggestion.placePrediction.secondaryText ? suggestion.placePrediction.secondaryText.text : ''}</div>
                                     </div>
                                 </div>
                             );
                         })}
                     </div>
                 </div>
             )}
        </div>
    );
};

const StepPlacesContent = ({ selectedPlaces, onSelectionChange, onNext, onBack }: StepPlacesProps) => {
  const [savedPlaces, setSavedPlaces] = useState<Place[]>([]);
  const [inputValue, setInputValue] = useState('');
  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

  useEffect(() => {
    fetchSavedPlaces();
  }, []);

  const fetchSavedPlaces = async () => {
    try {
      const response = await axios.get('/api/kiosk/saved-places');
      if (response.data && response.data.data) {
          setSavedPlaces(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch places', error);
    }
  };

  const visibleSavedPlaces = useMemo(() => {
    let places = savedPlaces;
    if (inputValue) {
        const lower = inputValue.toLowerCase();
        places = savedPlaces.filter(p => {
            const matches = p.name.toLowerCase().includes(lower) || (p.address && p.address.toLowerCase().includes(lower));
            const isSelected = selectedPlaces.some(sp => sp.google_place_id === p.google_place_id);
            return matches || isSelected;
        });
    }

    // Sort: Selected first
    return [...places].sort((a, b) => {
         const aSelected = selectedPlaces.some(sp => sp.google_place_id === a.google_place_id);
         const bSelected = selectedPlaces.some(sp => sp.google_place_id === b.google_place_id);
         if (aSelected && !bSelected) return -1;
         if (!aSelected && bSelected) return 1;
         return 0;
    });
  }, [savedPlaces, inputValue, selectedPlaces]);

  const togglePlace = (place: Place) => {
    const exists = selectedPlaces.find(p => p.google_place_id === place.google_place_id);
    if (exists) {
      onSelectionChange(selectedPlaces.filter(p => p.google_place_id !== place.google_place_id));
    } else {
      onSelectionChange([...selectedPlaces, place]);
    }
  };

  const handleSearchSelect = (place: Place) => {
      const exists = selectedPlaces.find(p => p.google_place_id === place.google_place_id);
      if (!exists) {
          onSelectionChange([...selectedPlaces, place]);
      }
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
            <label className="text-sm font-bold text-slate-500 uppercase tracking-widest ml-1">Caută Destinație (sau adaugă localitate nouă via Google)</label>
            <PlacesAutocomplete
                savedPlaces={savedPlaces}
                setSavedPlaces={setSavedPlaces}
                onSelectPlace={handleSearchSelect}
                inputValue={inputValue}
                setInputValue={setInputValue}
            />
         </div>

         <div className="space-y-4">
            <label className="text-sm font-bold text-slate-500 uppercase tracking-widest ml-1">Destinații Recurente</label>
            <div className="grid grid-cols-2 gap-4">
                {visibleSavedPlaces.map(place => {
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
                                <div className="w-20 h-20 rounded-xl bg-slate-200 flex items-center justify-center shrink-0">
                                    <span className="material-symbols-outlined text-3xl">location_city</span>
                                </div>
                            )}
                            <div className="flex-grow min-w-0">
                                <span className="text-xl font-bold block truncate">{place.name}</span>
                                <span className="text-sm opacity-70 truncate block">{place.address}</span>
                            </div>
                            <span className={`material-symbols-outlined text-4xl shrink-0 ${isSelected ? '' : 'opacity-20'}`}>
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
            className="w-full bg-green-600 hover:bg-green-700 text-white py-8 rounded-2xl text-4xl font-black shadow-xl shadow-green-500/20 flex items-center justify-center gap-4 active:scale-[0.98] transition-all uppercase tracking-tight disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span className="material-symbols-outlined text-5xl">directions_car</span>
            SELECTEAZĂ MAȘINA
        </button>
      </div>
    </div>
  );
};

const StepPlaces = (props: StepPlacesProps) => {
    return (
        <APIProvider apiKey={import.meta.env.VITE_GOOGLE_MAPS_API_KEY} libraries={['places']}>
            <StepPlacesContent {...props} />
        </APIProvider>
    );
};

export default StepPlaces;
