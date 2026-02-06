import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';

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
  // Use local time for date string to avoid timezone shifts
  const offset = date.getTimezoneOffset();
  const localDate = new Date(date.getTime() - (offset*60*1000));
  return localDate.toISOString().split('T')[0];
};

const ConcediuWizard = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const user = location.state?.user;
  const code = location.state?.code;

  // Initialize dates
  // Start: next working day
  // End: same as start
  const [startDate, setStartDate] = useState<Date>(() => getNextWorkingDay(new Date()));
  const [endDate, setEndDate] = useState<Date>(() => getNextWorkingDay(new Date()));

  // Calendar view state (months)
  const [startMonth, setStartMonth] = useState(new Date());
  const [endMonth, setEndMonth] = useState(new Date());

  // Sync initial view month with calculated dates
  useEffect(() => {
    setStartMonth(new Date(startDate));
    setEndMonth(new Date(endDate));
  }, []); // Run once on mount (after state init)

  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (!user) navigate('/');
  }, [user, navigate]);

  if (!user) return null;

  // Calculate total days
  // Reset hours to compare dates only
  const d1 = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
  const d2 = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());

  const diffTime = d2.getTime() - d1.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  const totalDays = diffDays > 0 ? diffDays : 0;

  const handleSubmit = async () => {
    if (totalDays <= 0) {
        alert("Data de sfârșit trebuie să fie după data de început.");
        return;
    }
    setIsLoading(true);
    try {
        await axios.post('/api/kiosk/leave-request', {
            user_id: user.id,
            code,
            start_date: formatDate(startDate),
            end_date: formatDate(endDate)
        });
        // Success feedback handled by alert for now as per plan,
        // ideally could show a success modal or toast.
        alert('Concediu înregistrat cu succes!');
        navigate('/');
    } catch (error: any) {
        alert(error.response?.data?.message || 'Eroare la înregistrare.');
    } finally {
        setIsLoading(false);
    }
  };

  // Calendar Component Logic
  const renderCalendar = (monthDate: Date, selectedDate: Date, onSelect: (d: Date) => void, onMonthChange: (d: Date) => void) => {
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Adjust for Monday start (0=Sun, 1=Mon, ..., 6=Sat) -> (Mon=0, ..., Sun=6)
    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const daysInMonth = lastDay.getDate();
    const days = [];

    // Empty cells
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push(<div key={`empty-${i}`} className="h-14"></div>);
    }

    // Days
    for (let d = 1; d <= daysInMonth; d++) {
        const currentDate = new Date(year, month, d);
        // Compare dates only
        const isSelected = currentDate.getDate() === selectedDate.getDate() &&
                           currentDate.getMonth() === selectedDate.getMonth() &&
                           currentDate.getFullYear() === selectedDate.getFullYear();

        days.push(
            <button
                key={d}
                onClick={() => onSelect(currentDate)}
                className={`h-14 flex items-center justify-center rounded-lg text-lg font-medium transition-colors ${
                    isSelected
                    ? 'bg-primary text-white font-bold shadow-lg shadow-primary/30'
                    : 'bg-primary/10 dark:bg-primary/20 dark:text-white hover:bg-gray-200 dark:hover:bg-slate-700'
                }`}
            >
                {d}
            </button>
        );
    }

    const monthName = monthDate.toLocaleDateString('ro-RO', { month: 'long', year: 'numeric' });
    const formattedMonth = monthName.charAt(0).toUpperCase() + monthName.slice(1);

    return (
        <div className="bg-background-light dark:bg-slate-800/50 p-6 rounded-xl border border-gray-100 dark:border-slate-700">
            <div className="flex items-center justify-between mb-6">
                <button onClick={() => onMonthChange(new Date(year, month - 1))} className="p-2 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-full">
                    <span className="material-symbols-outlined text-2xl">chevron_left</span>
                </button>
                <p className="text-xl font-bold text-[#111418] dark:text-white">{formattedMonth}</p>
                <button onClick={() => onMonthChange(new Date(year, month + 1))} className="p-2 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-full">
                    <span className="material-symbols-outlined text-2xl">chevron_right</span>
                </button>
            </div>
            <div className="grid grid-cols-7 text-center mb-2">
                <div className="text-gray-400 font-bold text-sm py-2">LU</div>
                <div className="text-gray-400 font-bold text-sm py-2">MA</div>
                <div className="text-gray-400 font-bold text-sm py-2">MI</div>
                <div className="text-gray-400 font-bold text-sm py-2">JO</div>
                <div className="text-gray-400 font-bold text-sm py-2">VI</div>
                <div className="text-primary font-bold text-sm py-2">SÂ</div>
                <div className="text-primary font-bold text-sm py-2">DU</div>
            </div>
            <div className="grid grid-cols-7 gap-1">
                {days}
            </div>
        </div>
    );
  };

  return (
    <div className="fixed inset-0 bg-slate-200 dark:bg-slate-900 z-40 overflow-hidden flex items-center justify-center font-sans p-4">
        <div className="relative w-full max-w-[1024px] h-auto lg:h-[768px] bg-white dark:bg-slate-900 rounded-xl shadow-2xl flex flex-col overflow-hidden border border-gray-200 dark:border-slate-800">
            {/* Header */}
            <div className="flex items-center justify-between px-8 py-6 border-b border-gray-100 dark:border-slate-800">
                <div className="flex items-center gap-4">
                    <span className="material-symbols-outlined text-primary text-4xl">event_available</span>
                    <h1 className="text-[#111418] dark:text-white tracking-tight text-[32px] font-bold leading-tight">Configurare Concediu</h1>
                </div>
                <button onClick={() => navigate('/')} className="p-3 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-full transition-colors text-[#111418] dark:text-white">
                    <span className="material-symbols-outlined text-4xl">close</span>
                </button>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto px-8 py-6">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div className="flex flex-col gap-4">
                        <h2 className="text-xl font-bold text-gray-500 dark:text-gray-400 px-2 flex items-center gap-2">
                            <span className="material-symbols-outlined">calendar_today</span>
                            DATA ÎNCEPUT
                        </h2>
                        {renderCalendar(startMonth, startDate, (d) => {
                            setStartDate(d);
                            // Auto adjust end date if it becomes before start date
                            if (d > endDate) setEndDate(d);
                        }, setStartMonth)}
                    </div>
                    <div className="flex flex-col gap-4">
                        <h2 className="text-xl font-bold text-gray-500 dark:text-gray-400 px-2 flex items-center gap-2">
                            <span className="material-symbols-outlined">calendar_today</span>
                            DATA SFÂRȘIT
                        </h2>
                        {renderCalendar(endMonth, endDate, setEndDate, setEndMonth)}
                    </div>
                </div>
            </div>

            {/* Footer */}
            <div className="bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 p-8 space-y-6">
                <div className="flex items-center justify-center bg-primary/5 dark:bg-primary/10 py-4 rounded-xl border border-primary/20">
                    <h2 className="text-primary dark:text-blue-400 text-3xl font-bold flex items-center gap-3">
                        <span className="material-symbols-outlined text-4xl">calculate</span>
                        Număr total de zile: <span className="underline underline-offset-4">{totalDays}</span>
                    </h2>
                </div>
                <div className="flex flex-col sm:flex-row gap-6">
                    <button
                        onClick={() => navigate('/')}
                        className="flex-1 h-20 flex items-center justify-center gap-3 bg-[#f0f2f4] dark:bg-slate-800 text-[#111418] dark:text-white text-2xl font-bold rounded-xl transition-all hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700"
                    >
                        <span className="material-symbols-outlined text-3xl">arrow_back</span>
                        ÎNAPOI
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={isLoading || totalDays <= 0}
                        className="flex-[2] h-20 flex items-center justify-center gap-3 bg-green-600 text-white text-2xl font-bold rounded-xl transition-all hover:opacity-90 shadow-xl shadow-green-600/20 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span className="material-symbols-outlined text-4xl">check_circle</span>
                        {isLoading ? 'SE PROCESEAZĂ...' : 'CONFIRMĂ CONCEDIU'}
                    </button>
                </div>
            </div>
        </div>
    </div>
  );
};

export default ConcediuWizard;
