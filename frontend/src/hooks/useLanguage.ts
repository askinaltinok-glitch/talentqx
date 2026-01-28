import { useEffect, useCallback } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
  SUPPORTED_LANGUAGES,
  SupportedLanguage,
  isSupportedLanguage,
  setStoredLanguage,
  DEFAULT_LANGUAGE,
} from '../i18n';

interface UseLanguageReturn {
  /** Current language code */
  currentLanguage: SupportedLanguage;
  /** Change language and navigate to new URL */
  changeLanguage: (lang: SupportedLanguage) => void;
  /** All supported languages */
  supportedLanguages: readonly SupportedLanguage[];
  /** Check if a language code is valid */
  isValidLanguage: (lang: string) => lang is SupportedLanguage;
}

/**
 * Hook to manage language state synchronized with URL and i18next
 */
export function useLanguage(): UseLanguageReturn {
  const { lang } = useParams<{ lang: string }>();
  const navigate = useNavigate();
  const location = useLocation();
  const { i18n } = useTranslation();

  // Determine current language from URL param or i18n
  const currentLanguage: SupportedLanguage =
    lang && isSupportedLanguage(lang) ? lang : (i18n.language as SupportedLanguage) || DEFAULT_LANGUAGE;

  // Sync i18next with URL language on mount and when URL changes
  useEffect(() => {
    if (lang && isSupportedLanguage(lang) && i18n.language !== lang) {
      i18n.changeLanguage(lang);
      setStoredLanguage(lang);
    }
  }, [lang, i18n]);

  // Change language and update URL
  const changeLanguage = useCallback(
    (newLang: SupportedLanguage) => {
      if (newLang === currentLanguage) return;

      // Update i18next
      i18n.changeLanguage(newLang);
      setStoredLanguage(newLang);

      // Update URL - replace language prefix in path
      const pathParts = location.pathname.split('/');

      // Check if first part is a language code
      if (pathParts.length > 1 && isSupportedLanguage(pathParts[1])) {
        pathParts[1] = newLang;
      } else {
        // Insert language at beginning
        pathParts.splice(1, 0, newLang);
      }

      const newPath = pathParts.join('/') || `/${newLang}`;
      navigate(newPath + location.search + location.hash, { replace: true });
    },
    [currentLanguage, i18n, location, navigate]
  );

  return {
    currentLanguage,
    changeLanguage,
    supportedLanguages: SUPPORTED_LANGUAGES,
    isValidLanguage: isSupportedLanguage,
  };
}

export default useLanguage;
