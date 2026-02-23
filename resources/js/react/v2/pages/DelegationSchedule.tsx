import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import Clock from '../components/Clock';
import TimePicker from '../components/TimePicker';

interface ScheduleDay {
  date: string;
  start: string;
  end: string;
}

interface ScheduleState {
    employee: { id: number; name?: string; first_name?: string; last_name?: string };
    delegation_start_time: string;
    timeline: ScheduleDay[];
    shift_settings: { start: string; end: string };
    code: string;
    next_step?: string;
}

const DelegationSchedule = () => {
  const { i18n, t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as ScheduleState;

  const [schedule, setSchedule] = useState<{date: string, start_time: string, end_time: string}[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!state || !state.timeline) {
        navigate('/');
        return;
    }

    const initialSchedule = state.timeline.map(day => ({
        date: day.date,
        start_time: day.start,
        end_time: day.end
    }));
    setSchedule(initialSchedule);
  }, [state, navigate]);

  const handleTimeChange = (index: number, field: 'start_time' | 'end_time', value: string) => {
      const newSchedule = [...schedule];
      newSchedule[index] = { ...newSchedule[index], [field]: value };
      setSchedule(newSchedule);
  };

  const handleDeleteDay = (index: number) => {
      setSchedule(prev => prev.filter((_, i) => i !== index));
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

  const isScheduleValid = schedule.every(day => isTimeValid(day.start_time, day.end_time));

  const handleSubmit = async () => {
      if (!isScheduleValid) {
          setError(t('delegation.invalid_times', 'Ora de sfârșit trebuie să fie după ora de început pentru toate zilele.'));
          return;
      }

      setIsLoading(true);
      setError(null);
      try {
          const response = await axios.post('/api/kiosk/end-delegation-schedule', {
              employee_id: state.employee.id,
              code: state.code,
              schedule: schedule,
              next_step: state.next_step
          });

          const data = response.data;

          if (data.type === 'leave_screen') {
              navigate('/leave', { state: { employee: data.employee, code: state.code } });
              return;
          }

          if (data.type === 'delegation_wizard') {
              navigate('/delegation', { state: { employee: data.employee } });
              return;
          }

          if (data.type === 'checkin' || data.type === 'checkout' || data.type === 'success') {
              navigate('/', { state: { success: data.message || t('delegation.end_success', 'Delegația a fost încheiată cu succes!') } });
              return;
          }

          navigate('/', { state: { success: t('delegation.end_success', 'Delegația a fost încheiată cu succes!') } });
      } catch (err: any) {
          setError(err.response?.data?.message || t('delegation.end_error', 'A apărut o eroare la salvarea programului.'));
      } finally {
          setIsLoading(false);
      }
  };

  if (!state) return null;

  return (
    <div className="flex flex-col p-4 md:p-6 gap-4 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-wrap justify-between items-center bg-white dark:bg-slate-800 p-4 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 gap-4">
        <div className="flex items-center gap-3">
            <button onClick={handleBack} className="bg-slate-200 dark:bg-slate-700 p-2 rounded-xl mr-1 hover:bg-slate-300 transition-colors">
                <span className="material-symbols-outlined">arrow_back</span>
            </button>
          <div className="flex flex-col">
              <h1 className="text-base md:text-xl font-bold leading-none">{t('delegation.schedule_title', 'Confirmare Program Delegație')}</h1>
              <span className="text-slate-500 dark:text-slate-400 text-[9px] md:text-xs">
                {state.employee?.name || `${state.employee?.first_name} ${state.employee?.last_name}`}
              </span>
          </div>
        </div>
        <div className="scale-75 md:scale-100 origin-right">
            <Clock />
        </div>
      </header>

      <div className="flex-1 overflow-y-auto pr-0 md:pr-2 custom-scrollbar min-h-0">
          {error && (
            <div className="bg-red-100 dark:bg-red-900/30 border-2 border-red-500 text-red-700 dark:text-red-400 p-2 md:p-3 mb-3 md:mb-4 rounded-xl shadow-md text-center font-bold text-sm md:text-lg animate-pulse">
                {error}
            </div>
          )}

          <div className="flex flex-col gap-2 md:gap-3 pb-4 md:pb-6">
              {schedule.map((day, index) => {
                  const dateObj = new Date(day.date + 'T00:00:00');
                  const weekday = dateObj.toLocaleDateString(i18n.language, { weekday: 'long' });
                  const displayDate = weekday.charAt(0).toUpperCase() + weekday.slice(1) + ', ' + day.date;
                  const valid = isTimeValid(day.start_time, day.end_time);

                  return (
                      <div key={day.date} className={`bg-white dark:bg-slate-800 p-3 md:p-4 rounded-xl shadow-sm border-2 transition-all ${
                          valid ? 'border-slate-200 dark:border-slate-700' : 'border-red-500 shadow-red-100 dark:shadow-red-900/10'
                      }`}>
                          <div className="flex flex-col md:flex-row md:items-center gap-3 md:gap-4">
                              <div className="flex-grow flex items-center gap-2 md:gap-3">
                                  <div className="bg-blue-500/10 text-blue-500 p-1.5 md:p-2 rounded-lg shrink-0">
                                      <span className="material-symbols-outlined text-base md:text-2xl">flight_takeoff</span>
                                  </div>
                                  <div className="flex flex-col">
                                      <span className="text-base md:text-xl font-black">{displayDate}</span>
                                      <span className="text-slate-400 font-bold text-[7px] md:text-[10px] uppercase tracking-tighter">Ziua {index + 1}</span>
                                      {!valid && <span className="text-red-500 font-bold text-[9px] md:text-xs">{t('delegation.error_sequence', 'Ora de sfârșit invalidă')}</span>}
                                  </div>
                              </div>
                              
                              <div className="flex items-center justify-between md:justify-start gap-2 md:gap-4 bg-slate-50 dark:bg-slate-900/50 p-1.5 md:p-2 px-2 md:px-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                  <div className="flex flex-col gap-0.5">
                                      <label className="text-[7px] md:text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Start</label>
                                      <TimePicker
                                          value={day.start_time}
                                          onChange={(val) => handleTimeChange(index, 'start_time', val)}
                                          className="w-[100px] md:w-[160px]"
                                      />
                                  </div>
                                  <div className="text-slate-300 dark:text-slate-600 self-center mt-2 md:mt-3">
                                      <span className="material-symbols-outlined text-sm md:text-xl">arrow_forward</span>
                                  </div>
                                  <div className="flex flex-col gap-0.5">
                                      <label className="text-[7px] md:text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Sfârșit</label>
                                      <TimePicker
                                          value={day.end_time}
                                          onChange={(val) => handleTimeChange(index, 'end_time', val)}
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
            disabled={isLoading || !isScheduleValid}
            className={`px-4 md:px-6 py-2 md:py-3 rounded-xl bg-green-600 text-white font-bold text-sm md:text-lg shadow-lg shadow-green-600/30 flex items-center gap-1.5 md:gap-2 transition-all active:scale-95 ${
                isLoading || !isScheduleValid ? 'opacity-50 grayscale' : 'hover:bg-green-700'
            }`}
          >
              {isLoading ? (
                  <>
                    <span className="animate-spin material-symbols-outlined text-sm md:text-xl">sync</span>
                    {t('common.saving', '...')}
                  </>
              ) : (
                  <>
                    <span className="material-symbols-outlined text-sm md:text-xl">check_circle</span>
                    {t('delegation.btn_end', 'Finalizează')}
                  </>
              )}
          </button>
      </div>
    </div>
  );
};

export default DelegationSchedule;
