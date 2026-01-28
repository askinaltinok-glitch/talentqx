import { Link } from 'react-router-dom';
import { HomeIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../routes';

/**
 * Public 404 page for routes outside /app
 */
export function PublicNotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 via-white to-blue-50 px-4">
      <div className="text-center">
        <h1 className="text-9xl font-bold text-primary-600">404</h1>
        <h2 className="mt-4 text-3xl font-bold text-gray-900">Sayfa Bulunamadi</h2>
        <p className="mt-2 text-lg text-gray-600">
          Aradiginiz sayfa mevcut degil veya tasinmis olabilir.
        </p>
        <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            to={ROUTES.HOME}
            className="btn-primary inline-flex items-center justify-center"
          >
            <HomeIcon className="h-5 w-5 mr-2" />
            Ana Sayfaya Don
          </Link>
          <Link
            to={ROUTES.LOGIN}
            className="btn-secondary inline-flex items-center justify-center"
          >
            Giris Yap
          </Link>
        </div>
      </div>
    </div>
  );
}

/**
 * App 404 page for routes inside /app/*
 */
export function AppNotFound() {
  return (
    <div className="flex flex-col items-center justify-center py-16 px-4">
      <div className="text-center">
        <h1 className="text-8xl font-bold text-primary-600">404</h1>
        <h2 className="mt-4 text-2xl font-bold text-gray-900">Sayfa Bulunamadi</h2>
        <p className="mt-2 text-gray-600">
          Aradiginiz sayfa mevcut degil veya yetkiniz bulunmuyor.
        </p>
        <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            to={ROUTES.DASHBOARD}
            className="btn-primary inline-flex items-center justify-center"
          >
            <HomeIcon className="h-5 w-5 mr-2" />
            Dashboard'a Don
          </Link>
          <button
            onClick={() => window.history.back()}
            className="btn-secondary inline-flex items-center justify-center"
          >
            <ArrowLeftIcon className="h-5 w-5 mr-2" />
            Geri Don
          </button>
        </div>
      </div>
    </div>
  );
}
