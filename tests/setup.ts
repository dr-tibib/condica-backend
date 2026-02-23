import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock localStorage
const localStorageMock = (function() {
  let store: Record<string, string> = {};
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString();
    },
    clear: () => {
      store = {};
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    length: Object.keys(store).length,
    key: (index: number) => Object.keys(store)[index] || null,
  };
})();

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
});

// Mock fetch
global.fetch = vi.fn();

// Mock react-i18next
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
