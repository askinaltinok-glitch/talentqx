/**
 * Centralized route definitions for TalentQX
 * All route paths should be defined here to avoid hard-coded strings
 */

import i18n from './i18n';

// ============================================
// Route Path Constants (without language prefix)
// ============================================

export const ROUTES = {
  // Public routes
  HOME: '/',
  LOGIN: '/login',

  // App routes (protected)
  APP: '/app',
  DASHBOARD: '/app',
  JOBS: '/app/jobs',
  JOB_DETAIL: '/app/jobs/:id',
  CANDIDATES: '/app/candidates',
  CANDIDATE_DETAIL: '/app/candidates/:id',
  INTERVIEWS: '/app/interviews/:id',

  // Workforce Assessment routes
  EMPLOYEES: '/app/employees',
  EMPLOYEE_DETAIL: '/app/employees/:id',
  ASSESSMENTS: '/app/assessments',
  ASSESSMENTS_COMPARE: '/app/assessments/compare',

  // Marketplace routes (Premium only)
  MARKETPLACE: '/app/marketplace',
  MARKETPLACE_CANDIDATES: '/app/marketplace',
  MARKETPLACE_MY_REQUESTS: '/app/marketplace/my-requests',
  MARKETPLACE_CANDIDATE_DETAIL: '/app/marketplace/candidates/:id',

  // Sales Console routes (Platform Admin Only)
  LEADS: '/app/leads',
  LEAD_DETAIL: '/app/leads/:id',

  // Admin routes (Platform Admin Only)
  ADMIN_COMPANIES: '/app/admin/companies',

  // Error pages
  UNAUTHORIZED: '/app/unauthorized',
} as const;

// ============================================
// Language-aware Route Helper Functions
// ============================================

/**
 * Get current language from i18n
 */
function getCurrentLang(): string {
  return i18n.language || 'en';
}

/**
 * Prefix a route with the current language
 */
export function localizedPath(path: string): string {
  const lang = getCurrentLang();
  if (path.startsWith('/')) {
    return `/${lang}${path}`;
  }
  return `/${lang}/${path}`;
}

/**
 * Generate job detail URL with language prefix
 */
export function jobDetailPath(id: string): string {
  return localizedPath(`/app/jobs/${id}`);
}

/**
 * Generate candidate detail URL with language prefix
 */
export function candidateDetailPath(id: string): string {
  return localizedPath(`/app/candidates/${id}`);
}

/**
 * Generate interview detail URL with language prefix
 */
export function interviewDetailPath(id: string): string {
  return localizedPath(`/app/interviews/${id}`);
}

/**
 * Generate employee detail URL with language prefix
 */
export function employeeDetailPath(id: string): string {
  return localizedPath(`/app/employees/${id}`);
}

/**
 * Generate assessments compare URL with language prefix
 */
export function assessmentsComparePath(ids: string[]): string {
  return localizedPath(`/app/assessments/compare?ids=${ids.join(',')}`);
}

/**
 * Generate lead detail URL with language prefix
 */
export function leadDetailPath(id: string): string {
  return localizedPath(`/app/leads/${id}`);
}

/**
 * Generate marketplace candidate detail URL with language prefix
 */
export function marketplaceCandidateDetailPath(id: string): string {
  return localizedPath(`/app/marketplace/candidates/${id}`);
}

/**
 * Generate marketplace URL with language prefix
 */
export function marketplacePath(): string {
  return localizedPath('/app/marketplace');
}

/**
 * Generate login URL with optional redirect and language prefix
 */
export function loginPath(next?: string): string {
  const basePath = localizedPath('/login');
  if (next) {
    return `${basePath}?next=${encodeURIComponent(next)}`;
  }
  return basePath;
}

/**
 * Generate home URL with language prefix
 */
export function homePath(): string {
  return localizedPath('/');
}

/**
 * Generate dashboard URL with language prefix
 */
export function dashboardPath(): string {
  return localizedPath('/app');
}

/**
 * Generate leads URL with language prefix
 */
export function leadsPath(): string {
  return localizedPath('/app/leads');
}

// ============================================
// Navigation Items (for Layout)
// These use translation keys, not hardcoded strings
// ============================================

export const NAV_ITEMS = {
  main: [
    { nameKey: 'nav.dashboard', href: ROUTES.DASHBOARD, end: true },
    { nameKey: 'nav.jobs', href: ROUTES.JOBS, end: false },
    { nameKey: 'nav.candidates', href: ROUTES.CANDIDATES, end: false },
  ],
  marketplace: [
    { nameKey: 'nav.marketplace', href: ROUTES.MARKETPLACE, end: false },
  ],
  workforce: [
    { nameKey: 'nav.employees', href: ROUTES.EMPLOYEES, end: false },
    { nameKey: 'nav.assessments', href: ROUTES.ASSESSMENTS, end: false },
  ],
  sales: [
    { nameKey: 'nav.leads', href: ROUTES.LEADS, end: false },
  ],
  admin: [
    { nameKey: 'nav.adminCompanies', href: ROUTES.ADMIN_COMPANIES, end: false },
  ],
} as const;
