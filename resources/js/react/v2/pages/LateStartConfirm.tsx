import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import Clock from '../components/Clock';
import TimePicker from '../components/TimePicker';

interface LateStartState {
    threshold: string;
    employee: { id: number; first_name: string; last_name: string };
    code: string;
    workplace_id: number;
}

const LateStartConfirm = () => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as LateStartState;

  const [time, setTime] = useState(new Date().toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' }));
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!state) return null;

  const handleAction = async (action: 'start' | 'end') => {
      setIsLoading(true);
      setError(null);
      try {
          const response = await axios.post('/api/kiosk/handle-late-start', {
              code: state.code,
              action,
              time,
              workplace_id: state.workplace_id
          });
          
          navigate('/', { state: { success: response.data.message } });
      } catch (err: any) {
          setError(err.response?.data?.message || t('late_start.error', 'A apărut o eroare.'));
      } finally {
          setIsLoading(false);
      }
  };

  return (
    <div className="flex flex-col p-4 md:p-6 gap-4 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-wrap justify-between items-center bg-white dark:bg-slate-800 p-4 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 gap-4">
        <div className="flex items-center gap-3">
            <button onClick={() => navigate('/')} className="bg-slate-200 dark:bg-slate-700 p-2 rounded-xl mr-1 hover:bg-slate-300 transition-colors">
                <span className="material-symbols-outlined">arrow_back</span>
            </button>
          <div className="flex flex-col">
              <h1 className="text-lg md:text-xl font-bold leading-none">{t('late_start.title', 'Activitate Târzie')}</h1>
              <span className="text-slate-500 dark:text-slate-400 text-[10px] md:text-xs">{state.employee?.first_name} {state.employee?.last_name}</span>
          </div>
        </div>
        <Clock />
      </header>

      <main className="flex-1 flex flex-col items-center justify-center gap-4 md:gap-6 max-w-2xl mx-auto w-full py-4">
          <div className="bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 p-3 md:p-4 rounded-full">
              <span className="material-symbols-outlined text-4xl md:text-6xl">warning</span>
          </div>

          <div className="text-center px-4">
              <h2 className="text-xl md:text-2xl font-bold mb-2">
                  {t('late_start.question', 'Este trecut de ora {{threshold}}', { threshold: state.threshold })}
              </h2>
              <p className="text-base md:text-lg text-slate-500 dark:text-slate-400">
                  {t('late_start.instruction', 'Te rugăm să specifici dacă începi o tură nouă sau o închei pe cea curentă.')}
              </p>
          </div>

          <div className="bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 w-full max-w-[160px] md:max-w-[200px] flex flex-col items-center">
              <label className="text-[8px] md:text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 md:mb-3">{t('late_start.specify_time', 'Specifică Ora')}</label>
              <TimePicker value={time} onChange={setTime} />
          </div>

          {error && (
            <div className="bg-red-100 text-red-600 p-3 rounded-xl font-bold text-center w-full text-sm md:text-base">
                {error}
            </div>
          )}

          <div className="grid grid-cols-2 gap-3 md:gap-4 w-full">
              <button
                onClick={() => handleAction('start')}
                disabled={isLoading}
                className="h-16 md:h-20 rounded-2xl bg-primary text-white font-bold text-lg md:text-xl shadow-xl shadow-blue-600/30 active:scale-95 transition-all flex flex-col items-center justify-center gap-1 hover:bg-blue-600"
              >
                  <span className="material-symbols-outlined text-xl md:text-2xl">login</span>
                  {t('late_start.btn_start', 'Încep Tura')}
              </button>
              <button
                onClick={() => handleAction('end')}
                disabled={isLoading}
                className="h-16 md:h-20 rounded-2xl bg-white dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 text-slate-700 dark:text-white font-bold text-lg md:text-xl shadow-lg active:scale-95 transition-all flex flex-col items-center justify-center gap-1 hover:bg-slate-50 dark:hover:bg-slate-600"
              >
                  <span className="material-symbols-outlined text-xl md:text-2xl">logout</span>
                  {t('late_start.btn_end', 'Închei Tura')}
              </button>
          </div>
      </main>
    </div>
  );
};

export default LateStartConfirm;
