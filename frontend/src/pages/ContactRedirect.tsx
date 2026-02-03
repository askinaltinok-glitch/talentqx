import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

/**
 * Redirects /contact to the landing page demo section
 * The demo form serves as the primary contact method
 */
export default function ContactRedirect() {
  const { lang } = useParams<{ lang?: string }>();
  const navigate = useNavigate();
  const locale = lang && ['en', 'tr'].includes(lang) ? lang : 'tr';

  useEffect(() => {
    // Redirect to landing page with demo section anchor
    navigate(`/${locale}#demo`, { replace: true });
  }, [locale, navigate]);

  return null;
}
