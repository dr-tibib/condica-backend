import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import Clock from '../components/Clock';

interface CancelState {
    active_delegation: { id: number };
    employee: { id: number; first_name: string; last_name: string };
    code: string;
}

const DelegationCancel = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as CancelState;

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!state) return null;

  const handleCancel = async () => {
      setIsLoading(true);
      setError(null);
      try {
          const response = await axios.post('/api/kiosk/cancel-delegation', {
              code: state.code,
              presence_event_id: state.active_delegation.id
          });
          
          navigate('/', { state: { success: response.data.message } });
      } catch (err: any) {
          setError(err.response?.data?.message || t('delegation_cancel.error', 'A apărut o eroare.'));
      } finally {
          setIsLoading(false);
      }
  };

  const handleContinue = () => {
      navigate('/');
  };

  return (
    <div className="flex flex-col p-4 md:p-6 gap-4 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-wrap justify-between items-center bg-white dark:bg-slate-800 p-4 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 gap-4">
        <div className="flex items-center gap-3">
            <button onClick={() => navigate('/')} className="bg-slate-200 dark:bg-slate-700 p-2 rounded-lg mr-1">
                <span className="material-symbols-outlined">arrow_back</span>
            </button>
          <div className="flex flex-col">
              <h1 className="text-lg md:text-2xl font-bold leading-none">{t('delegation_cancel.title', 'Anulează Delegația?')}</h1>
              <span className="text-slate-500 dark:text-slate-400 text-[10px] md:text-sm">{state.employee?.first_name} {state.employee?.last_name}</span>
          </div>
        </div>
        <Clock />
      </header>

      <main className="flex-1 flex flex-col items-center justify-center gap-6 md:gap-8 max-w-2xl mx-auto w-full py-6">
          <div className="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 md:p-6 rounded-full">
              <span className="material-symbols-outlined text-5xl md:text-7xl">cancel</span>
          </div>

          <div className="text-center px-4">
              <h2 className="text-2xl md:text-3xl font-bold mb-2 md:mb-4">
                  {t('delegation_cancel.question', 'Delegație începută recent')}
              </h2>
              <p className="text-base md:text-xl text-slate-500 dark:text-slate-400">
                  {t('delegation_cancel.subtitle', 'Această delegație a fost începută acum mai puțin de 10 minute. Dorești să o anulezi și să revii la tura normală?')}
              </p>
          </div>

          {error && (
            <div className="bg-red-100 text-red-600 p-3 rounded-xl font-bold text-center w-full text-sm md:text-base">
                {error}
            </div>
          )}

          <div className="grid grid-cols-2 gap-4 md:gap-6 w-full">
              <button
                onClick={handleCancel}
                disabled={isLoading}
                className="h-16 md:h-24 rounded-2xl bg-red-600 text-white font-bold text-lg md:text-2xl shadow-xl shadow-red-600/30 active:scale-95 transition-all"
              >
                  {t('delegation_cancel.btn_confirm', 'Da, Anulează')}
              </button>
              <button
                onClick={handleContinue}
                disabled={isLoading}
                className="h-16 md:h-24 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-lg md:text-2xl shadow-lg active:scale-95 transition-all"
              >
                  {t('delegation_cancel.btn_continue', 'Nu, Continuă')}
              </button>
          </div>
      </main>
    </div>
  );
};

export default DelegationCancel;
