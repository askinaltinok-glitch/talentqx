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
// NotFound Pages
import { PublicNotFound, AppNotFound } from './pages/NotFound';

// Components
import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';
import LanguageRedirect from './components/LanguageRedirect';

// i18n
import { SUPPORTED_LANGUAGES } from './i18n';

function App() {
  return (
    <BrowserRouter>
      <Toaster position="top-right" />
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

        {/* Language-prefixed routes */}
        {SUPPORTED_LANGUAGES.map((lang) => (
          <Route key={lang} path={`/${lang}`}>
            {/* Public routes */}
            <Route index element={<LandingPage />} />
            <Route path="login" element={<Login />} />

            {/* Protected Routes - HR Panel */}
            <Route
              path="app"
              element={
                <RequireAuth>
                  <Layout />
                </RequireAuth>
              }
            >
              <Route index element={<Dashboard />} />
              <Route path="jobs" element={<Jobs />} />
              <Route path="jobs/:id" element={<JobDetail />} />
              <Route path="candidates" element={<Candidates />} />
              <Route path="candidates/:id" element={<CandidateDetail />} />
              <Route path="interviews/:id" element={<InterviewDetail />} />
              {/* Workforce Assessment Routes */}
              <Route path="employees" element={<Employees />} />
              <Route path="employees/:id" element={<EmployeeDetail />} />
              <Route path="assessments" element={<AssessmentResults />} />
              {/* Sales Console Routes */}
              <Route path="leads" element={<Leads />} />
              <Route path="leads/:id" element={<LeadDetail />} />
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
