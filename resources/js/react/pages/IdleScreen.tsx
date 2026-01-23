import { useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from '../components/LanguageSwitcher';

declare global {
  interface Window {
    tenant: any;
  }
}

/**
 * Implements the design from docs/design/idle_screen
 */
const IdleScreen = () => {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const [companyName, setCompanyName] = useState(window.tenant?.company_name || 'Acme Corp HQ');
  const [logoUrl, setLogoUrl] = useState<string | null>(null);
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    fetch('/api/config')
      .then((res) => res.json())
      .then((data) => {
        if (data.company_name) setCompanyName(data.company_name);
        if (data.logo_url) setLogoUrl(data.logo_url);
      })
      .catch((err) => console.error('Failed to fetch config', err));

    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);

    return () => clearInterval(timer);
  }, []);

  const handleRegularFlow = () => {
    navigate('/code-entry', { state: { flow: 'regular' } });
  };

  const handleDelegationFlow = () => {
    navigate('/code-entry', { state: { flow: 'delegation' } });
  };

  const dateString = currentTime.toLocaleString(i18n.language, {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  });

  return (
    <div className="bg-background-light dark:bg-background-dark text-[#111318] dark:text-white font-display antialiased h-screen w-full overflow-hidden flex flex-col">
      {/* Main Layout Container */}
      <div className="flex-1 flex flex-col items-center justify-between w-full h-full p-8 md:p-12 max-w-[768px] mx-auto">
        {/* Header Section */}
        <div className="flex-1 flex flex-col justify-end items-center w-full pb-10">
          <div className="flex flex-col items-center gap-6">
            {/* Logo Placeholder */}
            <img
              src={logoUrl || '/images/oak_soft_logo.svg'}
              alt="Company Logo"
              className="h-20 w-auto object-contain"
            />
            {/* Welcome Title */}
            <h1 className="text-3xl md:text-[32px] font-bold leading-tight tracking-tight text-center text-[#111318] dark:text-white">
              {t('welcome', { company: companyName })}
            </h1>
          </div>
        </div>

        {/* Action Section */}
        <div className="flex-[2] flex flex-col items-center justify-center w-full gap-8">
          {/* Primary Action Button */}
          <button
            onClick={handleRegularFlow}
            className="group flex w-full max-w-[500px] h-[120px] cursor-pointer items-center justify-center gap-4 overflow-hidden rounded-xl bg-primary hover:bg-blue-600 active:bg-blue-700 transition-all duration-200 shadow-lg shadow-blue-500/20 active:scale-[0.98]"
          >
            <span className="material-symbols-outlined text-4xl text-white">touch_app</span>
            <span className="text-white text-2xl md:text-3xl font-bold leading-normal tracking-wide">
              Tap to Enter Your Code
            </span>
          </button>

          {/* Secondary Action Button */}
          <button
            onClick={handleDelegationFlow}
            className="flex w-full max-w-[280px] h-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
          >
            <span className="text-[#111318] dark:text-white text-lg font-semibold leading-normal tracking-wide">
              Delegation
            </span>
          </button>
        </div>

        {/* Footer Section */}
        <div className="flex-1 flex flex-col justify-end items-center w-full pb-8">
          <div className="flex items-center gap-2 opacity-80 mb-4">
            <span className="material-symbols-outlined text-xl">schedule</span>
            <p className="text-lg md:text-xl font-medium text-center">
              {dateString}
            </p>
          </div>
          <LanguageSwitcher />
        </div>
      </div>
    </div>
  );
};

export default IdleScreen;