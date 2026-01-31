import { useState } from 'react';
import { XMarkIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import { useTranslation } from 'react-i18next';
import toast from 'react-hot-toast';
import { createEmployee, type CreateEmployeePayload } from '../services/employees';
import api from '../services/api';

interface AddEmployeeModalProps {
  isOpen: boolean;
  onClose: () => void;
  onCreated: () => void;
}

export default function AddEmployeeModal({ isOpen, onClose, onCreated }: AddEmployeeModalProps) {
  const { t } = useTranslation('common');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState<CreateEmployeePayload>({
    first_name: '',
    last_name: '',
    current_role: '',
    email: '',
    phone: '',
    branch: '',
    department: '',
    notes: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error when user types
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.first_name.trim()) {
      newErrors.first_name = t('validation.required');
    }
    if (!formData.last_name.trim()) {
      newErrors.last_name = t('validation.required');
    }
    if (!formData.current_role.trim()) {
      newErrors.current_role = t('validation.required');
    }
    if (!formData.email?.trim() && !formData.phone?.trim()) {
      newErrors.contact = t('addEmployee.emailOrPhoneRequired');
    }
    if (formData.email?.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = t('validation.email');
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) return;

    setIsSubmitting(true);
    try {
      await createEmployee({
        first_name: formData.first_name.trim(),
        last_name: formData.last_name.trim(),
        current_role: formData.current_role.trim(),
        email: formData.email?.trim() || undefined,
        phone: formData.phone?.trim() || undefined,
        branch: formData.branch?.trim() || undefined,
        department: formData.department?.trim() || undefined,
        notes: formData.notes?.trim() || undefined,
      });

      toast.success(t('addEmployee.success'));
      handleClose();
      onCreated();
    } catch (error: unknown) {
      const errorMessage = api.getErrorMessage(error);
      if (errorMessage === 'email_or_phone_required') {
        setErrors({ contact: t('addEmployee.emailOrPhoneRequired') });
      } else if (errorMessage === 'duplicate_employee') {
        setErrors({ contact: t('addEmployee.duplicateEmployee') });
      } else {
        toast.error(errorMessage);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    setFormData({
      first_name: '',
      last_name: '',
      current_role: '',
      email: '',
      phone: '',
      branch: '',
      department: '',
      notes: '',
    });
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="flex items-center justify-center w-10 h-10 bg-primary-100 rounded-full">
              <UserPlusIcon className="h-5 w-5 text-primary-600" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900">
              {t('addEmployee.title')}
            </h2>
          </div>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-500 transition-colors"
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Name fields */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.firstName')} *
              </label>
              <input
                type="text"
                name="first_name"
                value={formData.first_name}
                onChange={handleChange}
                className={`input w-full ${errors.first_name ? 'border-red-500' : ''}`}
                placeholder={t('addEmployee.firstNamePlaceholder')}
              />
              {errors.first_name && (
                <p className="text-sm text-red-600 mt-1">{errors.first_name}</p>
              )}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.lastName')} *
              </label>
              <input
                type="text"
                name="last_name"
                value={formData.last_name}
                onChange={handleChange}
                className={`input w-full ${errors.last_name ? 'border-red-500' : ''}`}
                placeholder={t('addEmployee.lastNamePlaceholder')}
              />
              {errors.last_name && (
                <p className="text-sm text-red-600 mt-1">{errors.last_name}</p>
              )}
            </div>
          </div>

          {/* Role */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('addEmployee.role')} *
            </label>
            <input
              type="text"
              name="current_role"
              value={formData.current_role}
              onChange={handleChange}
              className={`input w-full ${errors.current_role ? 'border-red-500' : ''}`}
              placeholder={t('addEmployee.rolePlaceholder')}
            />
            {errors.current_role && (
              <p className="text-sm text-red-600 mt-1">{errors.current_role}</p>
            )}
          </div>

          {/* Contact fields */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.email')}
              </label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                className={`input w-full ${errors.email || errors.contact ? 'border-red-500' : ''}`}
                placeholder={t('addEmployee.emailPlaceholder')}
              />
              {errors.email && (
                <p className="text-sm text-red-600 mt-1">{errors.email}</p>
              )}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.phone')}
              </label>
              <input
                type="tel"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                className={`input w-full ${errors.contact ? 'border-red-500' : ''}`}
                placeholder={t('addEmployee.phonePlaceholder')}
              />
            </div>
          </div>
          {errors.contact && (
            <p className="text-sm text-red-600">{errors.contact}</p>
          )}

          {/* Branch & Department */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.branch')}
              </label>
              <input
                type="text"
                name="branch"
                value={formData.branch}
                onChange={handleChange}
                className="input w-full"
                placeholder={t('addEmployee.branchPlaceholder')}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('addEmployee.department')}
              </label>
              <input
                type="text"
                name="department"
                value={formData.department}
                onChange={handleChange}
                className="input w-full"
                placeholder={t('addEmployee.departmentPlaceholder')}
              />
            </div>
          </div>

          {/* Notes */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('addEmployee.notes')}
            </label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows={3}
              className="input w-full resize-none"
              placeholder={t('addEmployee.notesPlaceholder')}
            />
          </div>
        </form>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
          <button
            type="button"
            onClick={handleClose}
            className="btn-secondary"
            disabled={isSubmitting}
          >
            {t('buttons.cancel')}
          </button>
          <button
            type="submit"
            onClick={handleSubmit}
            disabled={isSubmitting}
            className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? (
              <>
                <span className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></span>
                {t('buttons.saving')}
              </>
            ) : (
              <>
                <UserPlusIcon className="h-5 w-5 mr-2" />
                {t('buttons.save')}
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
