import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import Clock from '../components/Clock';

// Helper to get next working day
const getNextWorkingDay = (date: Date) => {
  const next = new Date(date);
  next.setDate(next.getDate() + 1);
  while (next.getDay() === 0 || next.getDay() === 6) {
    next.setDate(next.getDate() + 1);
  }
  return next;
};

// Helper to format date YYYY-MM-DD
const formatDate = (date: Date) => {
  const offset = date.getTimezoneOffset();
  const localDate = new Date(date.getTime() - (offset*60*1000));
  return localDate.toISOString().split('T')[0];
};

const LeaveWizard = () => {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const employee = location.state?.employee;
  const code = location.state?.code;

  const [numDays, setNumDays] = useState('1');
  const [startDate, setStartDate] = useState<Date>(() => getNextWorkingDay(new Date()));
  const [endDate, setEndDate] = useState<Date>(() => getNextWorkingDay(new Date()));
  
  const [startMonth, setStartMonth] = useState(new Date());
  const [endMonth, setEndMonth] = useState(new Date());
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const daysInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (!employee) navigate('/');
    daysInputRef.current?.focus();
  }, [employee, navigate]);

  // Update End Date when Num Days or Start Date changes
  useEffect(() => {
    const days = parseInt(numDays) || 1;
    let current = new Date(startDate);
    let count = 1;
    
    while (count < days) {
        current.setDate(current.getDate() + 1);
        if (current.getDay() !== 0 && current.getDay() !== 6) { // Skip Sat/Sun
            count++;
        }
    }
    setEndDate(current);
    setEndMonth(new Date(current));
  }, [numDays, startDate]);

  if (!employee) return null;

  const handleSubmit = async () => {
    setError(null);
    setIsLoading(true);
    try {
        await axios.post('/api/kiosk/handle-leave-submission', {
            code,
            start_date: formatDate(startDate),
            end_date: formatDate(endDate),
            total_days: parseInt(numDays)
        });

        navigate('/', { state: { success: t('leave.success', 'Concediu înregistrat cu succes!') } });
    } catch (error: any) {
        setError(error.response?.data?.message || t('leave.error', 'Eroare la înregistrare.'));
    } finally {
        setIsLoading(false);
    }
  };

  const renderCalendar = (monthDate: Date, selectedDate: Date, onSelect: (d: Date) => void, onMonthChange: (d: Date) => void) => {
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;
    const daysInMonth = lastDay.getDate();
    const days = [];

    for (let i = 0; i < startDayOfWeek; i++) {
        days.push(<div key={`empty-${i}`} className="h-12 md:h-14"></div>);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const currentDate = new Date(year, month, d);
        const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;
        const isSelected = currentDate.getDate() === selectedDate.getDate() &&
                           currentDate.getMonth() === selectedDate.getMonth() &&
                           currentDate.getFullYear() === selectedDate.getFullYear();

        days.push(
            <button
                key={d}
                onClick={() => onSelect(currentDate)}
                className={`h-12 md:h-14 flex items-center justify-center rounded-lg text-lg font-medium transition-colors ${
                    isSelected ? 'bg-primary text-white font-bold shadow-lg' : 
                    isWeekend ? 'bg-slate-100 dark:bg-slate-800 text-slate-400' :
                    'bg-primary/5 dark:bg-primary/10 hover:bg-primary/20'
                }`}
            >
                {d}
            </button>
        );
    }

    const monthName = monthDate.toLocaleDateString(i18n.language, { month: 'long', year: 'numeric' });

    return (
        <div className="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
            <div className="flex items-center justify-between mb-4">
                <button onClick={() => onMonthChange(new Date(year, month - 1))} className="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full">
                    <span className="material-symbols-outlined">chevron_left</span>
                </button>
                <p className="font-bold capitalize">{monthName}</p>
                <button onClick={() => onMonthChange(new Date(year, month + 1))} className="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full">
                    <span className="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
            <div className="grid grid-cols-7 gap-1">
                {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map(day => (
                    <div key={day} className="h-8 flex items-center justify-center text-[10px] font-black text-slate-400">{day}</div>
                ))}
                {days}
            </div>
        </div>
    );
  };

  return (
    <div className="flex flex-col p-3 md:p-6 gap-3 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-wrap justify-between items-center bg-white dark:bg-slate-800 p-3 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 shrink-0 gap-3">
        <div className="flex items-center gap-2 md:gap-3">
            <button onClick={() => navigate('/')} className="bg-slate-200 dark:bg-slate-700 p-2 rounded-lg mr-1 md:mr-2">
                <span className="material-symbols-outlined text-xl md:text-2xl">arrow_back</span>
            </button>
          <div className="flex flex-col">
              <h1 className="text-lg md:text-2xl font-bold leading-none">{t('leave.title', 'Configurare Concediu')}</h1>
              <span className="text-slate-500 dark:text-slate-400 text-[10px] md:text-sm">{employee.first_name} {employee.last_name}</span>
          </div>
        </div>
        <div className="scale-75 md:scale-100 origin-right">
            <Clock />
        </div>
      </header>

      <div className="flex-1 flex flex-col lg:flex-row gap-4 md:gap-8 overflow-y-auto md:overflow-hidden min-h-0">
          {/* Left Side: Number of Days & Dates */}
          <div className="w-full lg:w-1/3 flex flex-col gap-3 md:gap-6">
              <div className="bg-white dark:bg-slate-800 p-4 md:p-8 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col items-center">
                  <label className="text-[10px] md:text-sm font-bold text-slate-400 uppercase tracking-widest mb-1 md:mb-4">{t('leave.number_of_days', 'Număr Zile')}</label>
                  <input 
                    ref={daysInputRef}
                    type="number" 
                    value={numDays}
                    onChange={(e) => setNumDays(e.target.value)}
                    className="w-full text-center bg-slate-50 dark:bg-slate-900 border-0 rounded-2xl py-3 md:py-6 text-3xl md:text-6xl font-black text-primary focus:ring-4 focus:ring-primary/20"
                    inputMode="numeric"
                  />
              </div>

              <div className="grid grid-cols-2 lg:grid-cols-1 gap-2 md:gap-4">
                  <div className="bg-white dark:bg-slate-800 p-2 md:p-4 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center text-center md:text-left">
                      <span className="text-slate-400 font-bold uppercase text-[8px] md:text-xs">{t('common.start', 'Început')}</span>
                      <span className="font-black text-sm md:text-xl text-primary">{startDate.toLocaleDateString(i18n.language)}</span>
                  </div>
                  <div className="bg-white dark:bg-slate-800 p-2 md:p-4 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row justify-between items-center text-center md:text-left">
                      <span className="text-slate-400 font-bold uppercase text-[8px] md:text-xs">{t('common.end', 'Sfârșit')}</span>
                      <span className="font-black text-sm md:text-xl text-slate-500">{endDate.toLocaleDateString(i18n.language)}</span>
                  </div>
              </div>

              {error && <div className="bg-red-100 text-red-600 p-3 rounded-xl font-bold text-center text-xs md:text-base">{error}</div>}
          </div>

          {/* Right Side: Calendars */}
          <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 md:overflow-y-auto pr-0 md:pr-2 pb-4 min-h-0">
              <div className="flex flex-col gap-1 md:gap-2">
                  <h3 className="text-[9px] md:text-xs font-black text-slate-400 uppercase tracking-widest ml-2">{t('leave.select_start', 'Selectează Data de Început')}</h3>
                  {renderCalendar(startMonth, startDate, setStartDate, setStartMonth)}
              </div>
              <div className="flex flex-col gap-1 md:gap-2 opacity-50 md:pointer-events-none">
                  <h3 className="text-[9px] md:text-xs font-black text-slate-400 uppercase tracking-widest ml-2">{t('leave.end_preview', 'Data de Sfârșit (Calculată)')}</h3>
                  {renderCalendar(endMonth, endDate, () => {}, setEndMonth)}
              </div>
          </div>
      </div>

      <div className="shrink-0 pt-3 pb-1 md:pb-0 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 md:gap-4">
          <button
            onClick={() => navigate('/')}
            className="px-4 md:px-8 py-2 md:py-4 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-base md:text-xl"
          >
              {t('common.cancel', 'Anulează')}
          </button>
          <button
            onClick={handleSubmit}
            disabled={isLoading || parseInt(numDays) <= 0}
            className="px-4 md:px-8 py-2 md:py-4 rounded-xl bg-orange-500 text-white font-bold text-base md:text-xl shadow-lg shadow-orange-500/30 flex items-center gap-2 hover:bg-orange-600 disabled:opacity-50"
          >
              {isLoading ? t('common.processing', '...') : t('leave.confirm', 'Confirmă')}
          </button>
      </div>
    </div>
  );
};

export default LeaveWizard;
