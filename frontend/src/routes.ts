/**
 * Centralized route definitions for TalentQX
 * All route paths should be defined here to avoid hard-coded strings
 */

// ============================================
// Route Path Constants
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
} as const;

// ============================================
// Route Helper Functions
// ============================================

/**
 * Generate job detail URL
 */
export function jobDetailPath(id: string): string {
  return `/app/jobs/${id}`;
}

/**
 * Generate candidate detail URL
 */
export function candidateDetailPath(id: string): string {
  return `/app/candidates/${id}`;
}

/**
 * Generate interview detail URL
 */
export function interviewDetailPath(id: string): string {
  return `/app/interviews/${id}`;
}

/**
 * Generate employee detail URL
 */
export function employeeDetailPath(id: string): string {
  return `/app/employees/${id}`;
}

/**
 * Generate assessments compare URL
 */
export function assessmentsComparePath(ids: string[]): string {
  return `/app/assessments/compare?ids=${ids.join(',')}`;
}

/**
 * Generate login URL with optional redirect
 */
export function loginPath(next?: string): string {
  if (next) {
    return `/login?next=${encodeURIComponent(next)}`;
  }
  return '/login';
}

// ============================================
// Navigation Items (for Layout)
// ============================================

export const NAV_ITEMS = {
  main: [
    { name: 'Dashboard', href: ROUTES.DASHBOARD, end: true },
    { name: 'Is Ilanlari', href: ROUTES.JOBS, end: false },
    { name: 'Adaylar', href: ROUTES.CANDIDATES, end: false },
  ],
  workforce: [
    { name: 'Calisanlar', href: ROUTES.EMPLOYEES, end: false },
    { name: 'Degerlendirmeler', href: ROUTES.ASSESSMENTS, end: false },
  ],
} as const;
