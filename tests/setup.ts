import '@testing-library/jest-dom';
import { vi } from 'vitest';

vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string, options?: any) => {
        if (key === 'welcome' && options?.company) {
            return `Welcome to ${options.company}`;
        }
        return key;
    },
    i18n: {
      changeLanguage: () => new Promise(() => {}),
      language: 'en',
      resolvedLanguage: 'en'
    },
  }),
  initReactI18next: {
    type: '3rdParty',
    init: () => {},
  },
}));
