import React, { useState } from 'react';

interface ConfirmModalProps {
  onConfirm: (code: string) => void;
  onCancel: () => void;
  isLoading: boolean;
}

const ConfirmModal = ({ onConfirm, onCancel, isLoading }: ConfirmModalProps) => {
  const [code, setCode] = useState('');

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center">
      <div className="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-700">
        <h2 className="text-3xl font-black text-center mb-6 text-slate-800 dark:text-white">Confirmare Identitate</h2>
        <div className="space-y-4">
            <p className="text-center text-slate-500 font-bold uppercase tracking-wider">Introduceți codul personal</p>
            <input 
                type="password" 
                value={code}
                onChange={(e) => {
                    const val = e.target.value.replace(/[^0-9]/g, '');
                    setCode(val);
                }}
                maxLength={4}
                className="w-full text-center text-6xl font-mono tracking-[1rem] p-4 rounded-xl border-2 border-slate-300 dark:border-slate-600 focus:border-primary focus:ring-4 focus:ring-primary/20 bg-slate-100 dark:bg-slate-900 outline-none"
                autoFocus
                onKeyDown={(e) => {
                    if (e.key === 'Enter' && code.length > 0) onConfirm(code);
                    if (e.key === 'Escape') onCancel();
                }}
            />
        </div>
        <div className="flex gap-4 mt-8">
            <button 
                onClick={onCancel}
                className="flex-1 py-4 rounded-xl font-bold bg-slate-200 text-slate-600 hover:bg-slate-300 transition-colors"
                disabled={isLoading}
            >
                ANULEAZĂ
            </button>
            <button 
                onClick={() => onConfirm(code)}
                disabled={code.length === 0 || isLoading}
                className="flex-1 py-4 rounded-xl font-bold bg-primary text-white hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
                {isLoading ? '...' : 'VALIDARE'}
            </button>
        </div>
      </div>
    </div>
  );
};

export default ConfirmModal;
