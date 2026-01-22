import { useTranslation } from 'react-i18next';
import { useState } from 'react';

const languages = {
  en: { nativeName: 'English', flag: '🇬🇧' },
  ro: { nativeName: 'Română', flag: '🇷🇴' },
  hu: { nativeName: 'Magyar', flag: '🇭🇺' },
  de: { nativeName: 'Deutsch', flag: '🇩🇪' },
};

export const LanguageSwitcher = () => {
  const { i18n } = useTranslation();
  const [isOpen, setIsOpen] = useState(false);

  // i18next might not have resolved language immediately or might be something like 'en-US'
  const currentLang = (i18n.resolvedLanguage || i18n.language || 'en').split('-')[0];

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 p-2 rounded-lg hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
        aria-label="Change Language"
      >
        <span className="text-2xl leading-none">{languages[currentLang as keyof typeof languages]?.flag || '🌐'}</span>
      </button>

      {isOpen && (
        <>
            <div className="fixed inset-0 z-40" onClick={() => setIsOpen(false)}></div>
            <div className="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 min-w-[160px] bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 py-1">
            {Object.keys(languages).map((lng) => (
                <button
                key={lng}
                onClick={() => {
                    i18n.changeLanguage(lng);
                    setIsOpen(false);
                }}
                className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-left ${
                    currentLang === lng ? 'bg-primary/5 text-primary font-semibold' : 'text-slate-700 dark:text-slate-200 font-medium'
                }`}
                >
                <span className="text-xl leading-none">{languages[lng as keyof typeof languages].flag}</span>
                <span>{languages[lng as keyof typeof languages].nativeName}</span>
                </button>
            ))}
            </div>
        </>
      )}
    </div>
  );
};
