import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

const successTypes = {
  checkin: {
    color: 'success',
    icon: 'check_circle',
    title: 'Checked In!',
    message: 'Have a productive day!',
  },
  checkout: {
    color: 'primary',
    icon: 'check',
    title: 'Checked Out!',
    message: 'See you tomorrow!',
  },
  'delegation-start': {
    color: 'orange-500',
    icon: 'check',
    title: 'Delegation Started!',
    message: "You're all set for today.",
  },
  'delegation-end': {
    color: 'success',
    icon: 'check',
    title: 'Delegation Ended!',
    message: 'Welcome back!',
  },
};

const SuccessScreen = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { type = 'checkin', name = 'John Davidson', time = '8:32 AM' } = location.state || {};

  const { color, icon, title, message } = successTypes[type as keyof typeof successTypes] || successTypes.checkin;

  useEffect(() => {
    const timer = setTimeout(() => {
      navigate('/');
    }, 4000);

    return () => clearTimeout(timer);
  }, [navigate]);

  return (
    <div className="bg-background-light dark:bg-background-dark font-display text-[#111318] dark:text-white flex items-center justify-center min-h-screen p-4 md:p-8">
      <div className="relative bg-white dark:bg-[#1e293b] w-full max-w-[768px] aspect-[768/1024] max-h-[1024px] rounded-lg shadow-2xl flex flex-col overflow-hidden ring-1 ring-black/5 dark:ring-white/10">
        <div className="flex-1 flex flex-col items-center justify-center w-full px-8 sm:px-16 space-y-10">
          <div className="flex items-center justify-center">
            <div className={`bg-${color}/10 dark:bg-${color}/20 rounded-full p-8 md:p-12`}>
              <span
                className={`material-symbols-outlined text-${color} text-[120px] md:text-[180px] leading-none`}
                style={{ fontVariationSettings: "'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 48" }}
              >
                {icon}
              </span>
            </div>
          </div>
          <div className="text-center space-y-4 max-w-lg mx-auto">
            <h1 className="text-[#111318] dark:text-white tracking-tight text-4xl md:text-[48px] font-bold leading-tight pb-2">
              {title}
            </h1>
            <h2 className="text-primary text-2xl md:text-[32px] font-bold leading-tight tracking-[-0.015em]">
              {name}
            </h2>
            <p className="text-slate-500 dark:text-slate-400 text-xl md:text-2xl font-medium leading-normal">
              {time}
            </p>
            <p className="text-slate-600 dark:text-slate-300 text-lg md:text-xl font-normal leading-normal pt-4">
              {message}
            </p>
          </div>
        </div>
        <div className="flex flex-col items-center justify-end pb-12 w-full px-12 md:px-24">
          <div className="w-full max-w-md flex flex-col items-center gap-4">
            <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
              <div className="h-full bg-primary w-full rounded-full animate-[progress_4s_linear_forwards]" style={{ animation: 'progress 4s linear forwards' }}></div>
            </div>
            <p className="text-slate-400 dark:text-slate-500 text-sm md:text-base font-medium text-center">
              Returning to home screen...
            </p>
          </div>
        </div>
      </div>
      <style>{`
        @keyframes progress {
          from { width: 0%; }
          to { width: 100%; }
        }
      `}</style>
    </div>
  );
};

export default SuccessScreen;