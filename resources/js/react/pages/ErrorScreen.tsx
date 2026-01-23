import { useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const ErrorScreen = () => {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const location = useLocation();
    const {
        title = t('error.delegation_not_available.title'),
        message = t('error.delegation_not_available.message')
    } = location.state || {};

    const handleOk = () => {
        navigate('/');
    };

  return (
    <div className="font-display bg-background-light dark:bg-background-dark text-[#111318] dark:text-white antialiased overflow-hidden">
      <div className="relative flex h-screen w-full flex-col items-center justify-center p-6">
        <div className="flex w-full max-w-[480px] flex-col items-center justify-center rounded-xl bg-white dark:bg-[#1C2333] p-8 shadow-sm ring-1 ring-gray-100 dark:ring-gray-800 md:p-12">
          <div className="mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-orange-50 dark:bg-orange-900/20">
            <span
              className="material-symbols-outlined text-[48px] text-orange-500"
              style={{ fontVariationSettings: "'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 48" }}
            >
              warning
            </span>
          </div>
          <div className="flex flex-col items-center gap-3 text-center">
            <h1 className="text-2xl font-bold leading-tight tracking-[-0.015em] text-[#111318] dark:text-white md:text-3xl">
              {title}
            </h1>
            <p className="max-w-[320px] text-base font-normal leading-normal text-gray-500 dark:text-gray-400">
              {message}
            </p>
          </div>
          <div className="mt-10 flex w-full flex-col items-center gap-4">
            <button
              onClick={handleOk}
              className="flex h-12 w-full max-w-[240px] cursor-pointer items-center justify-center rounded-xl bg-primary px-8 text-base font-bold text-white shadow-md transition-all hover:bg-blue-700 active:scale-95"
            >
              <span className="truncate">{t('common.ok')}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ErrorScreen;