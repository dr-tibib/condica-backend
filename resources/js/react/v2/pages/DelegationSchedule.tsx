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
    employee: { id: number; name: string };
    delegation_start_time: string;
    schedule_days: ScheduleDay[];
    shift_settings: { start: string; end: string };
    code: string;
}

const DelegationSchedule = () => {
  const { i18n } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const state = location.state as ScheduleState;

  const [schedule, setSchedule] = useState<{date: string, start_time: string, end_time: string}[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!state || !state.schedule_days) {
        navigate('/');
        return;
    }

    const initialSchedule = state.schedule_days.map(day => ({
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

  const handleSubmit = async () => {
      setIsLoading(true);
      setError(null);
      try {
          await axios.post('/api/kiosk/end-delegation-schedule', {
              employee_id: state.employee.id,
              code: state.code,
              schedule: schedule
          });

          navigate('/', { state: { success: 'Delegația a fost încheiată cu succes!' } });
      } catch (err: any) {
          setError(err.response?.data?.message || 'A apărut o eroare la salvarea programului.');
      } finally {
          setIsLoading(false);
      }
  };

  if (!state) return null;

  return (
    <div className="flex flex-col p-6 gap-5 h-screen w-screen overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex justify-between items-center bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0">
        <div className="flex items-center gap-3">
            <button onClick={handleBack} className="bg-slate-200 dark:bg-slate-700 p-2 rounded-lg mr-2">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
          <div className="flex flex-col">
              <h1 className="text-2xl font-bold leading-none">Confirmare Program Delegație</h1>
              <span className="text-slate-500 dark:text-slate-400 text-sm">{state.employee?.name}</span>
          </div>
        </div>
        <Clock />
      </header>

      <div className="flex-1 overflow-y-auto pr-2">
          {error && (
            <div className="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm" role="alert">
                <p className="font-bold">Eroare</p>
                <p>{error}</p>
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {schedule.map((day, index) => {
                  const dateObj = new Date(day.date + 'T00:00:00');
                  const weekday = dateObj.toLocaleDateString(i18n.language, { weekday: 'long' });
                  const displayDate = weekday.charAt(0).toUpperCase() + weekday.slice(1) + ', ' + day.date;

                  return (
                      <div key={day.date} className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 relative">
                          <div className="font-bold text-lg mb-3 border-b border-slate-100 dark:border-slate-700 pb-2 flex justify-between items-start">
                              <div className="flex flex-col">
                                  <span>Ziua {index + 1}</span>
                                  <span className="text-slate-500 dark:text-slate-400 font-normal text-sm capitalize">{displayDate}</span>
                              </div>
                              <button
                                  onClick={() => handleDeleteDay(index)}
                                  className="text-red-500 hover:text-red-700 transition-colors p-2 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20"
                                  title="Elimină ziua"
                              >
                                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                  </svg>
                              </button>
                          </div>
                          <div className="flex gap-4">
                              <div className="flex-1">
                                  <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Start</label>
                                  <TimePicker
                                      value={day.start_time}
                                      onChange={(val) => handleTimeChange(index, 'start_time', val)}
                                  />
                              </div>
                              <div className="flex-1">
                                  <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sfârșit</label>
                                  <TimePicker
                                      value={day.end_time}
                                      onChange={(val) => handleTimeChange(index, 'end_time', val)}
                                  />
                              </div>
                          </div>
                      </div>
                  );
              })}
          </div>
      </div>

      <div className="shrink-0 pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-4">
          <button
            onClick={handleBack}
            className="px-8 py-4 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xl active:scale-95 transition-transform"
          >
              Anulează
          </button>
          <button
            onClick={handleSubmit}
            disabled={isLoading}
            className={`px-8 py-4 rounded-xl bg-green-600 text-white font-bold text-xl shadow-lg shadow-green-600/30 active:scale-95 transition-transform flex items-center gap-2 ${isLoading ? 'opacity-70 cursor-not-allowed' : 'hover:bg-green-700'}`}
          >
              {isLoading ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Se salvează...
                  </>
              ) : (
                  <>
                    Încheie Delegația
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </>
              )}
          </button>
      </div>
    </div>
  );
};

export default DelegationSchedule;
