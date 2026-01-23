import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import GooglePlacesAutocomplete, { geocodeByPlaceId, getLatLng } from 'react-google-places-autocomplete';

const SearchLocationScreen = () => {
    const { t } = useTranslation();
    const navigate = useNavigate();

    const handleBack = () => {
        navigate('/delegation-locations');
    };

    const handleSelectLocation = (location: any) => {
        navigate('/confirm-location', { state: { location } });
    };

    const handleSelect = async (value: any) => {
        if (!value) return;

        try {
            const results = await geocodeByPlaceId(value.value.place_id);
            const { lat, lng } = await getLatLng(results[0]);

            const locationData = {
                place_id: value.value.place_id,
                name: value.value.structured_formatting.main_text,
                address: value.value.structured_formatting.secondary_text,
                latitude: lat,
                longitude: lng,
                icon: 'place'
            };

            handleSelectLocation(locationData);
        } catch (error) {
            console.error('Error fetching location details:', error);
        }
    };

  return (
    <div className="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-50 overflow-x-hidden">
        <div className="min-h-screen flex flex-col items-center justify-start py-8 px-4">
            <div className="w-full max-w-[768px] h-[1024px] bg-white dark:bg-[#1a202c] shadow-2xl rounded-2xl overflow-hidden flex flex-col relative border border-slate-200 dark:border-slate-700">
                <header className="flex items-center justify-between px-6 py-4 bg-white dark:bg-[#1a202c] border-b border-slate-100 dark:border-slate-800 z-10 shrink-0">
                    <div className="flex items-center gap-4">
                        <button onClick={handleBack} className="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                            <span className="material-symbols-outlined" style={{ fontSize: '28px' }}>arrow_back</span>
                        </button>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{t('search.select_location')}</h2>
                    </div>
                </header>
                <main className="flex-1 flex flex-col bg-white dark:bg-[#1a202c] p-6">
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        {t('search.search_label', 'Search for a place')}
                    </label>
                    <GooglePlacesAutocomplete
                        apiKey={import.meta.env.VITE_GOOGLE_MAPS_API_KEY}
                        selectProps={{
                            onChange: handleSelect,
                            placeholder: t('search.placeholder', 'Start typing...'),
                            styles: {
                                input: (provided: any) => ({
                                    ...provided,
                                    paddingTop: '8px',
                                    paddingBottom: '8px',
                                }),
                                control: (provided: any) => ({
                                    ...provided,
                                    borderRadius: '0.75rem',
                                    borderColor: '#e2e8f0', // slate-200
                                }),
                            },
                        }}
                    />
                </main>
            </div>
        </div>
    </div>
  );
};

export default SearchLocationScreen;
