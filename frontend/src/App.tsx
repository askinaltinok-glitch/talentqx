import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { ROUTES } from './routes';

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
// Sales Console Pages
import Leads from './pages/Leads';
import LeadDetail from './pages/LeadDetail';
// NotFound Pages
import { PublicNotFound, AppNotFound } from './pages/NotFound';

// Components
import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';

function App() {
  return (
    <BrowserRouter>
      <Toaster position="top-right" />
      <Routes>
        {/* Public Routes */}
        <Route path={ROUTES.HOME} element={<LandingPage />} />
        <Route path={ROUTES.LOGIN} element={<Login />} />

        {/* Protected Routes - HR Panel */}
        <Route
          path="/app"
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

        {/* Public 404 - catches all other unmatched routes */}
        <Route path="*" element={<PublicNotFound />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
