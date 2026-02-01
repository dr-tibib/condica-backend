import React, { useState, useEffect } from 'react';
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

  return (
    <div className="flex flex-col h-full bg-white dark:bg-slate-900 rounded-[40px] shadow-2xl overflow-hidden border border-slate-200 w-full max-w-5xl mx-auto my-auto relative">
      <div className="px-10 py-8 border-b border-slate-100 flex justify-between items-center">
        <h1 className="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tight">SELECTARE MAȘINĂ</h1>
        <button onClick={onBack} className="w-14 h-14 flex items-center justify-center rounded-2xl bg-slate-100 text-slate-500 active:bg-slate-200 transition-colors">
          <span className="material-symbols-outlined text-4xl">close</span>
        </button>
      </div>

      <div className="flex-grow p-10 flex flex-col gap-8 overflow-y-auto scroll-hide">
         <div className="space-y-3">
            <label className="text-base font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">DESTINAȚII SELECTATE:</label>
            <div className="flex flex-wrap gap-3">
                {selectedPlaces.map((place, idx) => (
                    <span key={idx} className="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold text-xl flex items-center gap-3 shadow-md">
                        {place.name}
                        <span className="material-symbols-outlined text-2xl">location_on</span>
                    </span>
                ))}
            </div>
         </div>

         <div className="flex flex-col gap-4 flex-grow">
            <label className="text-base font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">ALEGE VEHICULUL DISPONIBIL</label>
            <div className="grid grid-cols-2 gap-6 h-full pb-2">
                {vehicles.map(v => {
                    const isSelected = selectedVehicleId === v.id;
                    return (
                        <button 
                            key={v.id}
                            onClick={() => onSelectVehicle(v.id)}
                            className={`flex items-center gap-6 p-8 rounded-[32px] border-4 transition-all ${isSelected ? 'border-primary bg-blue-50 text-primary shadow-xl ring-4 ring-blue-100/50' : 'border-slate-100 bg-white text-slate-800 hover:bg-slate-50 active:scale-95'}`}
                        >
                            <div className={`w-20 h-20 rounded-2xl flex items-center justify-center shrink-0 ${isSelected ? 'bg-primary text-white' : 'bg-slate-100 text-slate-400'}`}>
                                <span className="material-symbols-outlined text-5xl">directions_car</span>
                            </div>
                            <span className="text-4xl font-black font-mono tracking-tight">{v.license_plate}</span>
                        </button>
                    );
                })}
            </div>
         </div>
      </div>

      <div className="p-10 bg-slate-50 border-t border-slate-100">
        <div className="flex gap-6">
            <button 
                onClick={onBack}
                className="flex-1 bg-slate-400 hover:bg-slate-500 text-white h-24 rounded-[28px] text-3xl font-black flex items-center justify-center gap-4 active:scale-95 transition-all uppercase tracking-tight shadow-lg"
            >
                <span className="material-symbols-outlined text-4xl">arrow_back</span>
                ÎNAPOI
            </button>
            <button 
                onClick={onStart}
                disabled={!selectedVehicleId}
                className="flex-1 bg-success hover:bg-green-700 text-white h-24 rounded-[28px] text-3xl font-black flex items-center justify-center gap-4 active:scale-95 transition-all uppercase tracking-tight shadow-xl shadow-green-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span className="material-symbols-outlined text-4xl">rocket_launch</span>
                PORNEȘTE
            </button>
        </div>
      </div>
    </div>
  );
};

export default StepVehicle;
