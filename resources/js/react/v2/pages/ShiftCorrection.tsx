import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import Clock from '../components/Clock';
import TimePicker from '../components/TimePicker';

interface TimelineDay {
  date: string;
  start: string;
  end: string;
}

interface CorrectionState {
    employee: { id: number; first_name: string; last_name: string };
    last_start: string;
    timeline: TimelineDay[];
    code: string;
}

const ShiftCorrection = () => {
  const { i18n, t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as CorrectionState;

  const [timeline, setTimeline] = useState<TimelineDay[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!state || !state.timeline) {
        navigate('/');
        return;
    }
    setTimeline(state.timeline);
  }, [state, navigate]);

  const handleTimeChange = (index: number, field: 'start' | 'end', value: string) => {
      const newTimeline = [...timeline];
      newTimeline[index] = { ...newTimeline[index], [field]: value };
      setTimeline(newTimeline);
  };

  const handleDeleteDay = (index: number) => {
      setTimeline(prev => prev.filter((_, i) => i !== index));
  };

  const handleBack = () => {
      navigate('/');
  };

  const isTimeValid = (start: string, end: string) => {
      if (!start || !end) return false;
      const [sH, sM] = start.split(':').map(Number);
      const [eH, eM] = end.split(':').map(Number);
      
      const startMinutes = sH * 60 + sM;
      const endMinutes = eH * 60 + eM;
      
      return startMinutes < endMinutes;
  };

  const isTimelineValid = timeline.every(day => isTimeValid(day.start, day.end));

  const handleSubmit = async () => {
      if (!isTimelineValid) {
          setError(t('correction.invalid_times', 'Ora de sfârșit trebuie să fie după ora de început pentru toate zilele.'));
          return;
      }

      setIsLoading(true);
      setError(null);
      try {
          await axios.post('/api/kiosk/handle-shift-correction', {
              code: state.code,
              timeline: timeline
          });

          navigate('/', { state: { success: t('correction.success', 'Pontajul a fost corectat cu succes!') } });
      } catch (err: any) {
          setError(err.response?.data?.message || t('correction.error', 'A apărut o eroare la salvarea corecției.'));
      } finally {
          setIsLoading(false);
      }
  };

  if (!state) return null;

  return (
    <div className="flex flex-col p-3 md:p-6 gap-3 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-wrap justify-between items-center bg-white dark:bg-slate-800 p-3 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 gap-3">
        <div className="flex items-center gap-2 md:gap-3">
            <button onClick={handleBack} className="bg-slate-200 dark:bg-slate-700 p-1.5 md:p-2 rounded-xl mr-1 hover:bg-slate-300 transition-colors">
                <span className="material-symbols-outlined text-xl md:text-2xl">arrow_back</span>
            </button>
          <div className="flex flex-col">
              <h1 className="text-base md:text-xl font-bold leading-none">{t('correction.title', 'Corecție Pontaj')}</h1>
              <span className="text-slate-500 dark:text-slate-400 text-[9px] md:text-xs">{state.employee?.first_name} {state.employee?.last_name}</span>
          </div>
        </div>
        <div className="scale-75 md:scale-100 origin-right">
            <Clock />
        </div>
      </header>

      <div className="bg-orange-50 dark:bg-orange-900/20 p-2 md:p-3 rounded-xl border border-orange-200 dark:border-orange-800 shrink-0 shadow-sm flex items-center gap-2 md:gap-3">
          <span className="material-symbols-outlined text-orange-500 text-lg md:text-2xl">info</span>
          <p className="text-orange-800 dark:text-orange-200 font-bold text-[10px] md:text-base">
              {t('correction.info', 'Se pare că ai uitat să închei tura anterioară. Te rugăm să completezi orele pentru zilele lipsă.')}
          </p>
      </div>

      <div className="flex-1 overflow-y-auto pr-0 md:pr-2 custom-scrollbar min-h-0">
          {error && (
            <div className="bg-red-100 dark:bg-red-900/30 border-2 border-red-500 text-red-700 dark:text-red-400 p-2 md:p-3 mb-3 md:mb-4 rounded-xl shadow-md text-center font-bold text-sm md:text-lg animate-pulse">
                {error}
            </div>
          )}

          <div className="flex flex-col gap-2 md:gap-3 pb-4 md:pb-6">
              {timeline.map((day, index) => {
                  const dateObj = new Date(day.date + 'T00:00:00');
                  const weekday = dateObj.toLocaleDateString(i18n.language, { weekday: 'long' });
                  const displayDate = weekday.charAt(0).toUpperCase() + weekday.slice(1) + ', ' + day.date;
                  const valid = isTimeValid(day.start, day.end);

                  return (
                      <div key={day.date} className={`bg-white dark:bg-slate-800 p-3 md:p-4 rounded-xl shadow-sm border-2 transition-all ${
                          valid ? 'border-slate-200 dark:border-slate-700' : 'border-red-500 shadow-red-100 dark:shadow-red-900/10'
                      }`}>
                          <div className="flex flex-col md:flex-row md:items-center gap-3 md:gap-4">
                              <div className="flex-grow flex items-center gap-2 md:gap-3">
                                  <div className="bg-primary/10 text-primary p-1.5 md:p-2 rounded-lg shrink-0">
                                      <span className="material-symbols-outlined text-base md:text-2xl">calendar_today</span>
                                  </div>
                                  <div className="flex flex-col">
                                      <span className="text-base md:text-xl font-black">{displayDate}</span>
                                      {!valid && <span className="text-red-500 font-bold text-[9px] md:text-xs">{t('correction.error_sequence', 'Ora de sfârșit invalidă')}</span>}
                                  </div>
                              </div>
                              
                              <div className="flex items-center justify-between md:justify-start gap-2 md:gap-4 bg-slate-50 dark:bg-slate-900/50 p-1.5 md:p-2 px-2 md:px-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                  <div className="flex flex-col gap-0.5">
                                      <label className="text-[7px] md:text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Start</label>
                                      <TimePicker
                                          value={day.start}
                                          onChange={(val) => handleTimeChange(index, 'start', val)}
                                          className="w-[100px] md:w-[160px]"
                                      />
                                  </div>
                                  <div className="text-slate-300 dark:text-slate-600 self-center mt-2 md:mt-3">
                                      <span className="material-symbols-outlined text-sm md:text-xl">arrow_forward</span>
                                  </div>
                                  <div className="flex flex-col gap-0.5">
                                      <label className="text-[7px] md:text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Sfârșit</label>
                                      <TimePicker
                                          value={day.end}
                                          onChange={(val) => handleTimeChange(index, 'end', val)}
                                          className="w-[100px] md:w-[160px]"
                                      />
                                  </div>
                              </div>

                              <button
                                  onClick={() => handleDeleteDay(index)}
                                  className="self-end md:self-center text-slate-300 hover:text-red-500 transition-colors p-1.5 md:p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                              >
                                  <span className="material-symbols-outlined text-lg md:text-2xl">delete</span>
                              </button>
                          </div>
                      </div>
                  );
              })}
          </div>
      </div>

      <div className="shrink-0 pt-2 md:pt-3 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 md:gap-3">
          <button
            onClick={handleBack}
            className="px-4 md:px-6 py-2 md:py-3 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-sm md:text-lg active:scale-95 transition-all"
          >
              {t('common.cancel', 'Anulează')}
          </button>
          <button
            onClick={handleSubmit}
            disabled={isLoading || !isTimelineValid}
            className={`px-4 md:px-6 py-2 md:py-3 rounded-xl bg-primary text-white font-bold text-sm md:text-lg shadow-lg shadow-blue-600/30 flex items-center gap-1.5 md:gap-2 transition-all active:scale-95 ${
                isLoading || !isTimelineValid ? 'opacity-50 grayscale' : 'hover:bg-blue-600'
            }`}
          >
              {isLoading ? (
                  <>
                    <span className="animate-spin material-symbols-outlined text-sm md:text-xl">sync</span>
                    {t('common.saving', '...')}
                  </>
              ) : (
                  <>
                    <span className="material-symbols-outlined text-sm md:text-xl">save</span>
                    {t('common.save', 'Salvează')}
                  </>
              )}
          </button>
      </div>
    </div>
  );
};

export default ShiftCorrection;
