import { useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  getStoredLanguage,
  getBrowserLanguage,
  DEFAULT_LANGUAGE,
  SupportedLanguage,
} from '../i18n';

/**
 * Component that redirects from root (/) to the appropriate language-prefixed route.
 * Priority: localStorage > navigator.language > default (en)
 */
export default function LanguageRedirect() {
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    // Determine the target language
    let targetLang: SupportedLanguage;

    // 1. Check localStorage first
    const storedLang = getStoredLanguage();
    if (storedLang) {
      targetLang = storedLang;
    } else {
      // 2. Check browser language
      targetLang = getBrowserLanguage() || DEFAULT_LANGUAGE;
    }

    // Preserve any additional path after the root
    const additionalPath = location.pathname.substring(1); // Remove leading /
    const search = location.search;
    const hash = location.hash;

    // Navigate to the language-prefixed route
    const newPath = additionalPath
      ? `/${targetLang}/${additionalPath}`
      : `/${targetLang}`;

    navigate(newPath + search + hash, { replace: true });
  }, [navigate, location]);

  // Return null while redirecting
  return null;
}
