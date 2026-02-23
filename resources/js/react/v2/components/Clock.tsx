import React, { useEffect, useState } from 'react';

const Clock = () => {
  const [time, setTime] = useState(new Date());

  useEffect(() => {
    const interval = setInterval(() => setTime(new Date()), 1000);
    return () => clearInterval(interval);
  }, []);

  const format = (num: number) => num.toString().padStart(2, '0');
  const h = format(time.getHours());
  const m = format(time.getMinutes());
  const s = format(time.getSeconds());

  return (
    <div className="digital-clock flex items-center gap-0.5 md:gap-1">
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{h[0]}</span>
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{h[1]}</span>
      <div className="text-base md:text-3xl font-bold dark:text-slate-400 mx-0.5 md:mx-1">:</div>
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{m[0]}</span>
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{m[1]}</span>
      <div className="text-base md:text-3xl font-bold dark:text-slate-400 mx-0.5 md:mx-1">:</div>
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{s[0]}</span>
      <span className="bg-slate-800 text-white px-1 md:px-2 py-0.5 md:py-1 rounded text-base md:text-3xl font-mono">{s[1]}</span>
    </div>
  );
};

export default Clock;
