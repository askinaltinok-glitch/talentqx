import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

export interface Company {
  id: string;
  name: string;
  slug: string;
  logo_url: string | null;
}

export interface Branch {
  id: string;
  name: string;
  slug: string;
  address: string;
  city: string;
}

export interface Job {
  id: string;
  title: string;
  role_code: string;
  description: string;
  location: string;
  employment_type: string;
}

export interface ApplyData {
  company: Company;
  branch: Branch;
  job: Job;
}

export interface ApplyFormData {
  full_name: string;
  phone: string;
  email?: string;
  birth_year?: number;
  experience_years?: number;
  kvkk_consent: boolean;
  marketing_consent?: boolean;
}

export interface ApplyResult {
  candidate_id: string;
  message: string;
}

class ApplyService {
  private client = axios.create({
    baseURL: API_BASE_URL,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  async getJobInfo(companySlug: string, branchSlug: string, roleCode: string): Promise<ApplyData> {
    const response = await this.client.get(`/apply/${companySlug}/${branchSlug}/${roleCode}`);
    return response.data.data;
  }

  async submitApplication(
    companySlug: string,
    branchSlug: string,
    roleCode: string,
    formData: ApplyFormData
  ): Promise<ApplyResult> {
    const response = await this.client.post(`/apply/${companySlug}/${branchSlug}/${roleCode}`, formData);
    return response.data.data;
  }

  getErrorMessage(error: unknown): string {
    if (axios.isAxiosError(error)) {
      const responseData = error.response?.data;
      if (responseData?.message) {
        return responseData.message;
      }
      if (responseData?.errors) {
        const firstError = Object.values(responseData.errors)[0];
        if (Array.isArray(firstError) && firstError.length > 0) {
          return firstError[0] as string;
        }
      }
      return error.message || 'Bir hata oluştu';
    }
    return 'Beklenmeyen bir hata oluştu';
  }
}

export const applyService = new ApplyService();
export default applyService;
