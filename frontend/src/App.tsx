import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';

// Pages
import LandingPage from './pages/LandingPage';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Jobs from './pages/Jobs';
import JobDetail from './pages/JobDetail';
import Candidates from './pages/Candidates';
import CandidateDetail from './pages/CandidateDetail';
import InterviewDetail from './pages/InterviewDetail';
// Workforce Assessment Pages
import Employees from './pages/Employees';
import EmployeeDetail from './pages/EmployeeDetail';
import AssessmentResults from './pages/AssessmentResults';
import PublicAssessment from './pages/PublicAssessment';
import PublicInterview from './pages/PublicInterview';
import PublicApply from './pages/PublicApply';
// Sales Console Pages
import Leads from './pages/Leads';
import LeadDetail from './pages/LeadDetail';
// Admin Pages
import { AdminCompanies } from './pages/admin';
// Marketplace Pages
import { MarketplaceCandidates, MarketplaceMyRequests, MarketplaceCandidateFullProfile } from './pages/marketplace';
import PublicMarketplaceAccess from './pages/PublicMarketplaceAccess';
// NotFound & Unauthorized Pages
import { PublicNotFound, AppNotFound } from './pages/NotFound';
import Unauthorized from './pages/Unauthorized';
// Public Privacy Page
import PublicPrivacy from './pages/PublicPrivacy';
// Contact Redirect
import ContactRedirect from './pages/ContactRedirect';

// Components
import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';
import CustomerRouteGuard from './components/CustomerRouteGuard';
import LanguageRedirect from './components/LanguageRedirect';
import { SubscriptionProvider, ApiErrorHandler } from './components/subscription';

// i18n
import { SUPPORTED_LANGUAGES } from './i18n';

function App() {
  return (
    <BrowserRouter>
      <Toaster position="top-right" />
      <ApiErrorHandler />
      <Routes>
        {/* Root redirect - detect language and redirect */}
        <Route path="/" element={<LanguageRedirect />} />

        {/* Public Assessment Route (no auth required) */}
        <Route path="/assessment/:token" element={<PublicAssessment />} />
        <Route path="/a/:token" element={<PublicAssessment />} />

        {/* Public Interview Route (no auth required) */}
        <Route path="/interview/:role" element={<PublicInterview />} />
        <Route path="/i/:role" element={<PublicInterview />} />

        {/* Public Apply Route (no auth required) - QR Code Landing */}
        <Route path="/apply/:companySlug/:branchSlug/:roleCode" element={<PublicApply />} />

        {/* Public Privacy/KVKK Route (no auth required) */}
        <Route path="/privacy" element={<PublicPrivacy />} />

        {/* Public Contact Route (redirects to demo section) */}
        <Route path="/contact" element={<ContactRedirect />} />

        {/* Public Marketplace Access Route (token-based, no auth required) */}
        <Route path="/marketplace-access/:token" element={<PublicMarketplaceAccess />} />

        {/* Language-prefixed routes */}
        {SUPPORTED_LANGUAGES.map((lang) => (
          <Route key={lang} path={`/${lang}`}>
            {/* Public routes */}
            <Route index element={<LandingPage />} />
            <Route path="login" element={<Login />} />
            <Route path="privacy" element={<PublicPrivacy />} />
            <Route path="contact" element={<ContactRedirect />} />

            {/* Protected Routes - HR Panel */}
            {/* CustomerRouteGuard enforces default-deny for non-platform users */}
            <Route
              path="app"
              element={
                <RequireAuth>
                  <SubscriptionProvider>
                    <CustomerRouteGuard>
                      <Layout />
                    </CustomerRouteGuard>
                  </SubscriptionProvider>
                </RequireAuth>
              }
            >
              {/* Customer-allowed routes */}
              <Route index element={<Dashboard />} />
              <Route path="jobs" element={<Jobs />} />
              <Route path="jobs/:id" element={<JobDetail />} />
              <Route path="candidates" element={<Candidates />} />
              <Route path="candidates/:id" element={<CandidateDetail />} />
              <Route path="interviews/:id" element={<InterviewDetail />} />

              {/* Marketplace Routes (Premium only - access controlled by component) */}
              <Route path="marketplace" element={<MarketplaceCandidates />} />
              <Route path="marketplace/my-requests" element={<MarketplaceMyRequests />} />
              <Route path="marketplace/candidates/:id" element={<MarketplaceCandidateFullProfile />} />

              {/* Platform Admin Only Routes */}
              {/* These are blocked by CustomerRouteGuard for company users */}
              <Route path="employees" element={<Employees />} />
              <Route path="employees/:id" element={<EmployeeDetail />} />
              <Route path="assessments" element={<AssessmentResults />} />
              <Route path="leads" element={<Leads />} />
              <Route path="leads/:id" element={<LeadDetail />} />
              <Route path="admin/companies" element={<AdminCompanies />} />

              {/* 403 Unauthorized page - always accessible */}
              <Route path="unauthorized" element={<Unauthorized />} />
              {/* App 404 - catches unmatched /app/* routes */}
              <Route path="*" element={<AppNotFound />} />
            </Route>

            {/* Language-specific 404 */}
            <Route path="*" element={<PublicNotFound />} />
          </Route>
        ))}

        {/* Legacy routes - redirect to default language */}
        <Route path="/login" element={<Navigate to="/en/login" replace />} />
        <Route path="/app/*" element={<Navigate to="/en/app" replace />} />

        {/* Global 404 - redirect to language detection */}
        <Route path="*" element={<LanguageRedirect />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
