import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import all translation files
import enCommon from './locales/en/common.json';
import enLanding from './locales/en/landing.json';
import enAuth from './locales/en/auth.json';
import enSales from './locales/en/sales.json';

import trCommon from './locales/tr/common.json';
import trLanding from './locales/tr/landing.json';
import trAuth from './locales/tr/auth.json';
import trSales from './locales/tr/sales.json';

import deCommon from './locales/de/common.json';
import deLanding from './locales/de/landing.json';
import deAuth from './locales/de/auth.json';
import deSales from './locales/de/sales.json';

import frCommon from './locales/fr/common.json';
import frLanding from './locales/fr/landing.json';
import frAuth from './locales/fr/auth.json';
import frSales from './locales/fr/sales.json';

export const SUPPORTED_LANGUAGES = ['en', 'tr', 'de', 'fr'] as const;
export type SupportedLanguage = typeof SUPPORTED_LANGUAGES[number];

export const DEFAULT_LANGUAGE: SupportedLanguage = 'en';

export const LANGUAGE_NAMES: Record<SupportedLanguage, string> = {
  en: 'English',
  tr: 'Türkçe',
  de: 'Deutsch',
  fr: 'Français',
};

const resources = {
  en: {
    common: enCommon,
    landing: enLanding,
    auth: enAuth,
    sales: enSales,
  },
  tr: {
    common: trCommon,
    landing: trLanding,
    auth: trAuth,
    sales: trSales,
  },
  de: {
    common: deCommon,
    landing: deLanding,
    auth: deAuth,
    sales: deSales,
  },
  fr: {
    common: frCommon,
    landing: frLanding,
    auth: frAuth,
    sales: frSales,
  },
};

i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: DEFAULT_LANGUAGE,
    supportedLngs: SUPPORTED_LANGUAGES,

    // Namespaces
    ns: ['common', 'landing', 'auth', 'sales'],
    defaultNS: 'common',

    // Detection options
    detection: {
      // Order of language detection
      order: ['path', 'localStorage', 'navigator'],
      // Cache user language preference
      caches: ['localStorage'],
      // Look for language in URL path
      lookupFromPathIndex: 0,
      // LocalStorage key
      lookupLocalStorage: 'talentqx-language',
    },

    interpolation: {
      escapeValue: false, // React already escapes values
    },

    react: {
      useSuspense: false,
    },
  });

export default i18n;

/**
 * Check if a language code is supported
 */
export function isSupportedLanguage(lang: string): lang is SupportedLanguage {
  return SUPPORTED_LANGUAGES.includes(lang as SupportedLanguage);
}

/**
 * Get the browser's preferred language if supported, otherwise return default
 */
export function getBrowserLanguage(): SupportedLanguage {
  const browserLang = navigator.language.split('-')[0];
  return isSupportedLanguage(browserLang) ? browserLang : DEFAULT_LANGUAGE;
}

/**
 * Get the stored language preference from localStorage
 */
export function getStoredLanguage(): SupportedLanguage | null {
  const stored = localStorage.getItem('talentqx-language');
  if (stored && isSupportedLanguage(stored)) {
    return stored;
  }
  return null;
}

/**
 * Store language preference in localStorage
 */
export function setStoredLanguage(lang: SupportedLanguage): void {
  localStorage.setItem('talentqx-language', lang);
}
