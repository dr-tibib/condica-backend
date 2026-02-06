import React, { useEffect, useRef } from 'react';

interface CodeInputProps {
  value: string;
  onChange: (value: string) => void;
  onSubmit: () => void;
  isLoading?: boolean;
}

const CodeInput = ({ value, onChange, onSubmit, isLoading }: CodeInputProps) => {
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    // Focus input on mount
    inputRef.current?.focus();

    const handleKeyDown = (e: KeyboardEvent) => {
      // Allow standard navigation keys
      if (['Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;

      if (e.key >= '0' && e.key <= '9') {
        // Handled by input onChange usually, but if we want to force focus
        inputRef.current?.focus();
      } else if (e.key === 'Enter') {
        if (value.length > 0) onSubmit();
      } else if (e.key === 'Escape') {
         onChange('');
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [value, onSubmit, onChange]);

  return (
    <section className="flex items-center gap-4 bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
      <label className="text-3xl font-bold text-slate-700 dark:text-slate-300 px-4">Cod:</label>
      <input 
        ref={inputRef}
        value={value}
        onChange={(e) => {
            // Only allow numbers
            const val = e.target.value.replace(/[^0-9]/g, '');
            onChange(val);
        }}
        className="flex-grow text-4xl p-4 border-2 border-slate-300 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-900 focus:border-primary focus:ring-4 focus:ring-primary/20 transition-all outline-none font-mono" 
        placeholder="" 
        type="text" // Visible characters as requested
        autoFocus
        maxLength={10}
      />
      <button 
        onClick={onSubmit}
        disabled={isLoading || value.length === 0}
        className="bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-3xl font-bold py-4 px-12 rounded-xl transition-colors shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? '...' : 'Validare'}
      </button>
    </section>
  );
};

export default CodeInput;
