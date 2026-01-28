import { useState, useEffect } from 'react';
import { Outlet, NavLink, useNavigate, Link } from 'react-router-dom';
import {
  HomeIcon,
  BriefcaseIcon,
  UsersIcon,
  Bars3Icon,
  XMarkIcon,
  ArrowRightOnRectangleIcon,
  UserCircleIcon,
  UserGroupIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  BellIcon,
} from '@heroicons/react/24/outline';
import { useAuthStore } from '../stores/authStore';
import { ROUTES, NAV_ITEMS } from '../routes';
import api from '../services/api';
import type { FollowUpStats } from '../types';
import clsx from 'clsx';

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  [ROUTES.DASHBOARD]: HomeIcon,
  [ROUTES.JOBS]: BriefcaseIcon,
  [ROUTES.CANDIDATES]: UsersIcon,
  [ROUTES.EMPLOYEES]: UserGroupIcon,
  [ROUTES.ASSESSMENTS]: ClipboardDocumentListIcon,
  [ROUTES.LEADS]: ChartBarIcon,
};

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [followUpCount, setFollowUpCount] = useState(0);
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();

  // Load follow-up count on mount and periodically
  useEffect(() => {
    const loadFollowUpCount = async () => {
      try {
        const data = await api.get<FollowUpStats>('/leads/follow-up-stats');
        setFollowUpCount(data.total_due);
      } catch (error) {
        // Silently fail - not critical
      }
    };

    loadFollowUpCount();
    // Refresh every 5 minutes
    const interval = setInterval(loadFollowUpCount, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  const handleLogout = async () => {
    await logout();
    navigate(ROUTES.LOGIN);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Mobile sidebar */}
      <div
        className={clsx(
          'fixed inset-0 z-50 lg:hidden',
          sidebarOpen ? 'block' : 'hidden'
        )}
      >
        <div
          className="fixed inset-0 bg-gray-900/80"
          onClick={() => setSidebarOpen(false)}
        />
        <div className="fixed inset-y-0 left-0 w-72 bg-white">
          <div className="flex h-16 items-center justify-between px-6 border-b">
            <span className="text-xl font-bold text-primary-600">TalentQX</span>
            <button onClick={() => setSidebarOpen(false)}>
              <XMarkIcon className="h-6 w-6" />
            </button>
          </div>
          <nav className="p-4 space-y-1">
            {NAV_ITEMS.main.map((item) => {
              const Icon = iconMap[item.href] || HomeIcon;
              return (
                <NavLink
                  key={item.name}
                  to={item.href}
                  end={item.end}
                  onClick={() => setSidebarOpen(false)}
                  className={({ isActive }) =>
                    clsx(
                      'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                      isActive
                        ? 'bg-primary-50 text-primary-700'
                        : 'text-gray-700 hover:bg-gray-100'
                    )
                  }
                >
                  <Icon className="h-5 w-5" />
                  {item.name}
                </NavLink>
              );
            })}
            <div className="pt-4 mt-4 border-t border-gray-200">
              <p className="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Yetkinlik Değerlendirme
              </p>
              {NAV_ITEMS.workforce.map((item) => {
                const Icon = iconMap[item.href] || UserGroupIcon;
                return (
                  <NavLink
                    key={item.name}
                    to={item.href}
                    end={item.end}
                    onClick={() => setSidebarOpen(false)}
                    className={({ isActive }) =>
                      clsx(
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                        isActive
                          ? 'bg-primary-50 text-primary-700'
                          : 'text-gray-700 hover:bg-gray-100'
                      )
                    }
                  >
                    <Icon className="h-5 w-5" />
                    {item.name}
                  </NavLink>
                );
              })}
            </div>
            <div className="pt-4 mt-4 border-t border-gray-200">
              <p className="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Satış Konsolu
              </p>
              {NAV_ITEMS.sales.map((item) => {
                const Icon = iconMap[item.href] || ChartBarIcon;
                return (
                  <NavLink
                    key={item.name}
                    to={item.href}
                    end={item.end}
                    onClick={() => setSidebarOpen(false)}
                    className={({ isActive }) =>
                      clsx(
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                        isActive
                          ? 'bg-primary-50 text-primary-700'
                          : 'text-gray-700 hover:bg-gray-100'
                      )
                    }
                  >
                    <Icon className="h-5 w-5" />
                    {item.name}
                  </NavLink>
                );
              })}
            </div>
          </nav>
        </div>
      </div>

      {/* Desktop sidebar */}
      <div className="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col">
        <div className="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
          <div className="flex h-16 shrink-0 items-center">
            <span className="text-xl font-bold text-primary-600">TalentQX</span>
          </div>
          <nav className="flex flex-1 flex-col">
            <ul role="list" className="flex flex-1 flex-col gap-y-7">
              <li>
                <ul role="list" className="-mx-2 space-y-1">
                  {NAV_ITEMS.main.map((item) => {
                    const Icon = iconMap[item.href] || HomeIcon;
                    return (
                      <li key={item.name}>
                        <NavLink
                          to={item.href}
                          end={item.end}
                          className={({ isActive }) =>
                            clsx(
                              'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                              isActive
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-700 hover:bg-gray-100'
                            )
                          }
                        >
                          <Icon className="h-5 w-5" />
                          {item.name}
                        </NavLink>
                      </li>
                    );
                  })}
                </ul>
              </li>
              <li>
                <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                  Yetkinlik Değerlendirme
                </div>
                <ul role="list" className="-mx-2 space-y-1">
                  {NAV_ITEMS.workforce.map((item) => {
                    const Icon = iconMap[item.href] || UserGroupIcon;
                    return (
                      <li key={item.name}>
                        <NavLink
                          to={item.href}
                          end={item.end}
                          className={({ isActive }) =>
                            clsx(
                              'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                              isActive
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-700 hover:bg-gray-100'
                            )
                          }
                        >
                          <Icon className="h-5 w-5" />
                          {item.name}
                        </NavLink>
                      </li>
                    );
                  })}
                </ul>
              </li>
              <li>
                <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                  Satış Konsolu
                </div>
                <ul role="list" className="-mx-2 space-y-1">
                  {NAV_ITEMS.sales.map((item) => {
                    const Icon = iconMap[item.href] || ChartBarIcon;
                    return (
                      <li key={item.name}>
                        <NavLink
                          to={item.href}
                          end={item.end}
                          className={({ isActive }) =>
                            clsx(
                              'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium',
                              isActive
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-700 hover:bg-gray-100'
                            )
                          }
                        >
                          <Icon className="h-5 w-5" />
                          {item.name}
                        </NavLink>
                      </li>
                    );
                  })}
                </ul>
              </li>
              {/* Follow-up notification */}
              {followUpCount > 0 && (
                <li>
                  <Link
                    to="/leads?filter=follow_up"
                    className="flex items-center gap-3 px-3 py-2 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 hover:bg-yellow-100 transition-colors"
                  >
                    <BellIcon className="h-5 w-5" />
                    <span className="flex-1 text-sm font-medium">Takip Gereken</span>
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-500 text-xs font-bold text-white">
                      {followUpCount > 9 ? '9+' : followUpCount}
                    </span>
                  </Link>
                </li>
              )}
              <li className="mt-auto">
                <div className="flex items-center gap-3 px-3 py-2 text-sm">
                  <UserCircleIcon className="h-8 w-8 text-gray-400" />
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 truncate">
                      {user?.full_name}
                    </p>
                    <p className="text-xs text-gray-500 truncate">
                      {user?.email}
                    </p>
                  </div>
                  <button
                    onClick={handleLogout}
                    className="p-1 text-gray-400 hover:text-gray-600"
                    title="Cikis Yap"
                  >
                    <ArrowRightOnRectangleIcon className="h-5 w-5" />
                  </button>
                </div>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      {/* Main content */}
      <div className="lg:pl-72">
        {/* Mobile header */}
        <div className="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm lg:hidden">
          <div className="flex items-center gap-4">
            <button
              type="button"
              className="-m-2.5 p-2.5 text-gray-700"
              onClick={() => setSidebarOpen(true)}
            >
              <Bars3Icon className="h-6 w-6" />
            </button>
            <span className="text-lg font-bold text-primary-600">TalentQX</span>
          </div>
          {/* Follow-up notification */}
          <Link
            to="/leads?filter=follow_up"
            className="relative p-2 text-gray-500 hover:text-gray-700"
            title="Takip Gereken Leadler"
          >
            <BellIcon className="h-6 w-6" />
            {followUpCount > 0 && (
              <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                {followUpCount > 9 ? '9+' : followUpCount}
              </span>
            )}
          </Link>
        </div>

        <main className="p-4 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
