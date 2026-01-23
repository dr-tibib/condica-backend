import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const SearchLocationScreen = () => {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const [query, setQuery] = useState('Coffee Shop Downtown');

    const handleBack = () => {
        navigate('/delegation-locations');
    };

    const handleSelectLocation = (location: any) => {
        navigate('/confirm-location', { state: { location } });
    };

    const searchResults = [
        { id: 1, name: 'Central Perk Coffee', address: '123 Bedford St, New York, NY', distance: '0.1 mi', icon: 'storefront', active: true },
        { id: 2, name: 'Downtown Coffee House', address: '456 Main Ave, Seattle, WA', distance: '0.3 mi', icon: 'local_cafe' },
        { id: 3, name: 'Java Junction', address: '789 Broadway, San Francisco, CA', distance: '1.2 mi', icon: 'bakery_dining' },
    ];

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
                <main className="flex-1 flex flex-col overflow-y-auto bg-white dark:bg-[#1a202c]">
                    <div className="px-6 py-6 pb-2 shrink-0">
                        <label className="block relative group">
                            <div className="absolute -inset-0.5 bg-primary rounded-xl opacity-100 blur-[1px]"></div>
                            <div className="relative flex items-center w-full h-14 bg-white dark:bg-slate-800 rounded-xl overflow-hidden ring-1 ring-primary z-10">
                                <div className="pl-4 flex items-center justify-center text-primary">
                                    <span className="material-symbols-outlined" style={{ fontSize: '24px' }}>search</span>
                                </div>
                                <input
                                    autoFocus
                                    className="w-full h-full bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400 px-3 text-lg font-medium"
                                    type="text"
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                />
                                <button onClick={() => setQuery('')} className="pr-4 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                    <span className="material-symbols-outlined" style={{ fontSize: '24px' }}>cancel</span>
                                </button>
                            </div>
                        </label>
                    </div>
                    <div className="px-6 pt-4 pb-2 shrink-0">
                        <h3 className="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{t('search.search_results')}</h3>
                    </div>
                    <div className="flex-1 px-4 pb-6 flex flex-col gap-3">
                        {searchResults.map(result => (
                            <div
                                key={result.id}
                                onClick={() => handleSelectLocation(result)}
                                className={`group flex items-center justify-between p-4 rounded-xl cursor-pointer transition-all ${result.active ? 'bg-primary/10 dark:bg-primary/20 border border-primary/20 hover:shadow-sm' : 'bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700'}`}
                            >
                                <div className="flex items-center gap-4 overflow-hidden">
                                    <div className={`flex items-center justify-center size-14 rounded-full shrink-0 shadow-sm ${result.active ? 'bg-white dark:bg-slate-800 text-primary' : 'bg-white dark:bg-slate-700 text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-600'}`}>
                                        <span className={`material-symbols-outlined ${result.active ? 'filled' : ''}`} style={{ fontVariationSettings: result.active ? "'FILL' 1" : '', fontSize: '24px' }}>{result.icon}</span>
                                    </div>
                                    <div className="flex flex-col min-w-0">
                                        <p className="text-slate-900 dark:text-white text-lg font-semibold truncate">{result.name}</p>
                                        <p className="text-slate-500 dark:text-slate-400 text-sm font-normal truncate">{result.address}</p>
                                    </div>
                                </div>
                                <div className="shrink-0 pl-2">
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${result.active ? 'bg-primary text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'}`}>
                                        {result.distance}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </div>
    </div>
  );
};

export default SearchLocationScreen;