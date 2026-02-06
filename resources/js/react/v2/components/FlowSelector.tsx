import React from 'react';

interface FlowSelectorProps {
  selectedFlow: string;
  onSelectFlow: (flow: string) => void;
}

const FlowSelector = ({ selectedFlow, onSelectFlow }: FlowSelectorProps) => {
  return (
    <section className="grid grid-cols-3 gap-4">
      <button 
        onClick={() => onSelectFlow('regular')}
        className={`flex items-center justify-center gap-2 py-4 rounded-xl shadow-md border-2 transition-all active:scale-95 ${
          selectedFlow === 'regular'
            ? 'bg-primary text-white border-primary'
            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-primary/50'
        }`}
      >
        <span className="material-symbols-outlined text-3xl">check_circle</span>
        <span className="text-2xl font-bold">Normal</span>
      </button>
      <button 
        onClick={() => onSelectFlow('delegation')}
        className={`flex items-center justify-center gap-2 py-4 rounded-xl shadow-md border-2 transition-all active:scale-95 ${
            selectedFlow === 'delegation'
              ? 'bg-primary text-white border-primary'
              : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-primary/50'
        }`}
      >
        <span className="material-symbols-outlined text-3xl">business_center</span>
        <span className="text-2xl font-bold">Delegație</span>
      </button>
      <button 
        onClick={() => onSelectFlow('concediu')}
        className={`flex items-center justify-center gap-2 py-4 rounded-xl shadow-md border-2 transition-all active:scale-95 ${
            selectedFlow === 'concediu'
              ? 'bg-primary text-white border-primary'
              : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-primary/50'
        }`}
      >
        <span className="material-symbols-outlined text-3xl">event_available</span>
        <span className="text-2xl font-bold">Concediu</span>
      </button>
    </section>
  );
};

export default FlowSelector;
