import React, { useState, useEffect, useMemo } from 'react';
import axios from 'axios';

interface Vehicle {
  id: number;
  name: string;
  license_plate: string;
}

interface Place {
  name: string;
}

interface StepVehicleProps {
  selectedPlaces: Place[];
  selectedVehicleId: number | null;
  onSelectVehicle: (id: number) => void;
  onBack: () => void;
  onStart: () => void;
}

const StepVehicle = ({ selectedPlaces, selectedVehicleId, onSelectVehicle, onBack, onStart }: StepVehicleProps) => {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [searchText, setSearchText] = useState("");

  useEffect(() => {
    fetchVehicles();
  }, []);

  const fetchVehicles = async () => {
    try {
      const response = await axios.get('/api/kiosk/vehicles');
      setVehicles(response.data.data);
    } catch (error) {
      console.error('Failed to fetch vehicles', error);
    }
  };

  const filteredVehicles = useMemo(() => {
    if (!searchText) return vehicles;
    const lower = searchText.toLowerCase().replace(/\s/g, "");
    return vehicles.filter(v => 
        v.license_plate.toLowerCase().replace(/\s/g, "").includes(lower) ||
        v.name.toLowerCase().includes(lower)
    );
  }, [vehicles, searchText]);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
          setSearchText("");
      } else if (e.key === "Backspace") {
          setSearchText(prev => prev.slice(0, -1));
      } else if (e.key === "Enter" || e.code === "NumpadEnter") {
          // If only one vehicle is left after filtering, select it and start
          if (filteredVehicles.length === 1) {
              onSelectVehicle(filteredVehicles[0].id);
          }
      } else if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
          setSearchText(prev => prev + e.key);
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [filteredVehicles, onSelectVehicle]);

  return (
    <div className="flex flex-col h-full bg-white dark:bg-slate-900 md:rounded-[40px] shadow-2xl overflow-hidden border-0 md:border border-slate-200 w-full max-w-5xl mx-auto my-auto relative">
      <style>{`
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
      `}</style>

      {/* === HEADER === */}
      <div className="px-6 md:px-10 py-5 md:py-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 shrink-0">
        <div className="flex items-center gap-4 flex-1">
            <div className="w-12 h-12 md:w-16 md:h-16 bg-primary text-white rounded-2xl flex items-center justify-center text-2xl md:text-4xl shadow-lg shadow-primary/20">
                <span className="material-symbols-outlined">directions_car</span>
            </div>
            <div className="flex-1">
                <h1 className="text-xl md:text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tight leading-none">Selectare Mașină</h1>
                <p className="text-slate-400 text-[10px] md:text-xs font-black tracking-[0.2em] uppercase mt-1">Configurare Delegație</p>
            </div>
            
            {/* Search Display (Always visible or shows when typing) */}
            <div className={`h-16 px-6 rounded-2xl flex items-center gap-4 transition-all duration-300 border-2 ${searchText ? 'bg-blue-50 dark:bg-blue-900/20 border-primary min-w-[300px]' : 'bg-slate-100 dark:bg-slate-800 border-transparent'}`}>
                <span className={`material-symbols-outlined text-3xl ${searchText ? 'text-primary' : 'text-slate-400'}`}>search</span>
                <div className="flex-1 text-2xl font-black text-slate-800 dark:text-white uppercase tracking-wider font-mono">
                    {searchText || <span className="text-slate-300 dark:text-slate-600 text-sm font-bold tracking-widest">TIPĂREȘTE NR. AUTO...</span>}
                    {searchText && <span className="text-primary animate-[blink_1s_step-end_infinite]">|</span>}
                </div>
                {searchText && (
                    <button onClick={() => setSearchText("")} className="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-primary">
                        <span className="material-symbols-outlined text-3xl">close</span>
                    </button>
                )}
            </div>
        </div>
        
        <button onClick={onBack} className="ml-4 w-10 h-10 md:w-14 md:h-14 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 active:scale-90 transition-all shadow-sm">
          <span className="material-symbols-outlined text-2xl md:text-4xl">close</span>
        </button>
      </div>

      {/* === MAIN CONTENT === */}
      <div className="flex-grow p-6 md:p-10 flex flex-col gap-6 md:gap-8 overflow-y-auto scroll-hide">
         {/* Summary of places (Hide if searching to save space) */}
         {!searchText && (
             <div className="space-y-3 shrink-0">
                <label className="text-[10px] md:text-sm font-black text-slate-400 uppercase tracking-widest ml-1">Destinații Confirmate ({selectedPlaces.length}):</label>
                <div className="flex flex-wrap gap-2 md:gap-3">
                    {selectedPlaces.map((place, idx) => (
                        <div key={idx} className="bg-blue-50 dark:bg-blue-900/30 border border-primary/20 text-primary px-4 md:px-6 py-2 md:py-3 rounded-2xl font-bold text-sm md:text-xl flex items-center gap-2 md:gap-3 shadow-sm">
                            <span className="material-symbols-outlined text-lg md:text-2xl">location_on</span>
                            {place.name}
                        </div>
                    ))}
                </div>
             </div>
         )}

         {/* Vehicle Grid */}
         <div className="flex flex-col gap-4 flex-grow mt-2">
            <div className="flex justify-between items-end">
                <label className="text-[10px] md:text-sm font-black text-slate-400 uppercase tracking-widest ml-1">
                    {searchText ? `Rezultate Căutare (${filteredVehicles.length})` : "Alege vehiculul pentru deplasare:"}
                </label>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 pb-4">
                {filteredVehicles.map((v) => {
                    const isSelected = selectedVehicleId === v.id;
                    return (
                        <button 
                            key={v.id}
                            onClick={() => onSelectVehicle(v.id)}
                            className={`flex items-center gap-4 md:gap-6 p-4 md:p-8 rounded-[32px] border-2 md:border-[3px] transition-all duration-200 ${isSelected ? 'border-primary bg-blue-50 dark:bg-blue-900/20 text-primary shadow-xl scale-[1.02]' : 'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-slate-200'}`}
                        >
                            <div className={`w-14 h-14 md:w-20 md:h-20 rounded-2xl flex items-center justify-center shrink-0 transition-colors ${isSelected ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'}`}>
                                <span className="material-symbols-outlined text-3xl md:text-5xl">local_taxi</span>
                            </div>
                            <div className="text-left flex-grow">
                                <div className="text-[10px] md:text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{v.name}</div>
                                <span className="text-2xl md:text-5xl font-black font-mono tracking-tight">{v.license_plate}</span>
                            </div>
                            {isSelected && (
                                <span className="material-symbols-outlined text-3xl md:text-5xl text-primary">check_circle</span>
                            )}
                        </button>
                    );
                })}
            </div>

            {filteredVehicles.length === 0 && (
                <div className="flex flex-col items-center justify-center py-20 text-slate-400">
                    <span className="material-symbols-outlined text-6xl opacity-20 mb-4">search_off</span>
                    <p className="text-xl font-bold uppercase tracking-widest">Nu am găsit niciun vehicul.</p>
                    <button onClick={() => setSearchText("")} className="mt-4 text-primary font-bold uppercase tracking-widest hover:underline">Resetează Căutarea (ESC)</button>
                </div>
            )}
         </div>
      </div>

      {/* === FOOTER === */}
      <div className="p-6 md:p-10 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 shrink-0">
        <div className="flex gap-4 md:gap-6">
            <button 
                onClick={onBack}
                className="flex-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 h-16 md:h-24 rounded-3xl text-lg md:text-3xl font-black flex items-center justify-center gap-2 md:gap-4 active:scale-[0.98] transition-all uppercase tracking-tight shadow-sm"
            >
                <span className="material-symbols-outlined text-2xl md:text-4xl">arrow_back</span>
                Înapoi
            </button>
            <button 
                onClick={onStart}
                disabled={!selectedVehicleId}
                className={`flex-[2] h-16 md:h-24 rounded-3xl text-lg md:text-3xl font-black flex items-center justify-center gap-2 md:gap-4 active:scale-[0.98] transition-all uppercase tracking-tight shadow-xl shadow-green-500/20 ${selectedVehicleId ? 'bg-green-600 text-white shadow-green-500/40' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 cursor-not-allowed'}`}
            >
                <span className="material-symbols-outlined text-2xl md:text-4xl">rocket_launch</span>
                Pornește Delegația
            </button>
        </div>
      </div>
    </div>
  );
};

export default StepVehicle;
