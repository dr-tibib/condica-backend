import React from 'react';
import { useNavigate } from 'react-router-dom';

const FlowSelector = ({ onNormalClick }: { onNormalClick: () => void }) => {
  const navigate = useNavigate();

  return (
    <section className="grid grid-cols-3 gap-4">
      <button 
        onClick={onNormalClick}
        className="flex items-center justify-center gap-2 py-4 bg-primary text-white rounded-xl shadow-md border-2 border-primary transition-all active:scale-95 hover:bg-blue-700"
      >
        <span className="material-symbols-outlined text-3xl">check_circle</span>
        <span className="text-2xl font-bold">Normal</span>
      </button>
      <button 
        onClick={() => navigate('/delegation')}
        className="flex items-center justify-center gap-2 py-4 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 hover:border-primary/50 transition-all active:scale-95"
      >
        <span className="material-symbols-outlined text-3xl">business_center</span>
        <span className="text-2xl font-bold">Delegație</span>
      </button>
      <button 
        className="flex items-center justify-center gap-2 py-4 bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl shadow-sm border-2 border-slate-200 dark:border-slate-700 cursor-not-allowed"
        disabled
      >
        <span className="material-symbols-outlined text-3xl">event_available</span>
        <span className="text-2xl font-bold">Concediu</span>
      </button>
    </section>
  );
};

export default FlowSelector;
