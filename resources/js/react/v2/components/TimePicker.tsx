import React, { useEffect, useRef } from 'react';

interface TimePickerProps {
    value: string; // HH:mm
    onChange: (value: string) => void;
    className?: string;
}

const TimePicker: React.FC<TimePickerProps> = ({ value, onChange, className }) => {
    const [hours, minutes] = value ? value.split(':') : ['08', '00'];
    const minRef = useRef<HTMLInputElement>(null);

    const handleHourChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let val = e.target.value.replace(/[^0-9]/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        
        const numVal = parseInt(val);
        if (val.length === 2 && (numVal < 0 || numVal > 23)) return;

        onChange(`${val.padStart(2, '0').slice(-2)}:${minutes}`);
        
        // Auto-focus minutes if 2 digits entered
        if (val.length === 2) {
            minRef.current?.focus();
            minRef.current?.select();
        }
    };

    const handleMinuteChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let val = e.target.value.replace(/[^0-9]/g, '');
        if (val.length > 2) val = val.slice(0, 2);
        
        const numVal = parseInt(val);
        if (val.length === 2 && (numVal < 0 || numVal > 59)) return;

        onChange(`${hours}:${val.padStart(2, '0').slice(-2)}`);
    };

    const onBlur = (field: 'h' | 'm', val: string) => {
        const padded = val.padStart(2, '0').slice(-2);
        if (field === 'h') onChange(`${padded}:${minutes}`);
        else onChange(`${hours}:${padded}`);
    };

    const onFocus = (e: React.FocusEvent<HTMLInputElement>) => {
        e.target.select();
    };

    return (
        <div className={`flex gap-1 items-center ${className || ''}`}>
            <div className="relative flex-1">
                <input
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    value={hours}
                    onChange={handleHourChange}
                    onFocus={onFocus}
                    onBlur={(e) => onBlur('h', e.target.value)}
                    className="w-full p-2 rounded-lg border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xl font-mono text-center focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-slate-900 dark:text-white"
                    placeholder="HH"
                />
            </div>
            <span className="text-xl font-bold text-slate-400">:</span>
            <div className="relative flex-1">
                <input
                    ref={minRef}
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    value={minutes}
                    onChange={handleMinuteChange}
                    onFocus={onFocus}
                    onBlur={(e) => onBlur('m', e.target.value)}
                    className="w-full p-2 rounded-lg border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xl font-mono text-center focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-slate-900 dark:text-white"
                    placeholder="mm"
                />
            </div>
        </div>
    );
};

export default TimePicker;
