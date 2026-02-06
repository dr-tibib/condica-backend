import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import Clock from '../components/Clock';

interface ScheduleDay {
  date: string;
  start: string;
  end: string;
}

interface ScheduleState {
    user: { id: number; name: string };
    delegation_start_time: string;
    schedule_days: ScheduleDay[];
    shift_settings: { start: string; end: string };
    code: string;
}

const DelegationSchedule = () => {
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

  const handleBack = () => {
      navigate('/');
  };

  const handleSubmit = async () => {
      setIsLoading(true);
      setError(null);
      try {
          await axios.post('/api/kiosk/end-delegation-schedule', {
              user_id: state.user.id,
              code: state.code,
              schedule: schedule
          });

          alert('Delegația a fost încheiată cu succes!');
          navigate('/');
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
              <span className="text-slate-500 dark:text-slate-400 text-sm">{state.user?.name}</span>
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
              {schedule.map((day, index) => (
                  <div key={day.date} className="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                      <div className="font-bold text-lg mb-3 border-b border-slate-100 dark:border-slate-700 pb-2 flex justify-between">
                          <span>Ziua {index + 1}</span>
                          <span className="text-slate-500 font-normal text-base">{day.date}</span>
                      </div>
                      <div className="flex gap-4">
                          <div className="flex-1">
                              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Start</label>
                              <input
                                  type="time"
                                  value={day.start_time}
                                  onChange={(e) => handleTimeChange(index, 'start_time', e.target.value)}
                                  className="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-xl font-mono focus:ring-2 focus:ring-blue-500 outline-none"
                              />
                          </div>
                          <div className="flex-1">
                              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sfârșit</label>
                              <input
                                  type="time"
                                  value={day.end_time}
                                  onChange={(e) => handleTimeChange(index, 'end_time', e.target.value)}
                                  className="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-xl font-mono focus:ring-2 focus:ring-blue-500 outline-none"
                              />
                          </div>
                      </div>
                  </div>
              ))}
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
