import React from 'react';

interface TimePickerProps {
    value: string; // HH:mm
    onChange: (value: string) => void;
    className?: string;
}

const TimePicker: React.FC<TimePickerProps> = ({ value, onChange, className }) => {
    // Ensure value is HH:mm
    const [hours, minutes] = value ? value.split(':') : ['08', '00'];

    const handleHourChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        onChange(`${e.target.value}:${minutes}`);
    };

    const handleMinuteChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        onChange(`${hours}:${e.target.value}`);
    };

    const hourOptions = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
    const minuteOptions = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

    return (
        <div className={`flex gap-2 items-center ${className || ''}`}>
            <div className="relative flex-1">
                <select
                    value={hours}
                    onChange={handleHourChange}
                    className="w-full p-3 pr-8 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-xl font-mono focus:ring-2 focus:ring-blue-500 outline-none appearance-none text-slate-900 dark:text-white"
                >
                    {hourOptions.map(h => <option key={h} value={h}>{h}</option>)}
                </select>
                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-700 dark:text-slate-300">
                    <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
            </div>
            <span className="text-xl font-bold text-slate-400">:</span>
            <div className="relative flex-1">
                <select
                    value={minutes}
                    onChange={handleMinuteChange}
                    className="w-full p-3 pr-8 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-xl font-mono focus:ring-2 focus:ring-blue-500 outline-none appearance-none text-slate-900 dark:text-white"
                >
                    {minuteOptions.map(m => <option key={m} value={m}>{m}</option>)}
                </select>
                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-700 dark:text-slate-300">
                    <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
            </div>
        </div>
    );
};

export default TimePicker;
