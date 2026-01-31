import api from "./api";

export type CreateEmployeePayload = {
  first_name: string;
  last_name: string;
  current_role: string;
  email?: string;
  phone?: string;
  branch?: string;
  department?: string;
  notes?: string;
};

export type Employee = {
  id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string | null;
  phone: string | null;
  current_role: string;
  branch: string | null;
  department: string | null;
  status: string;
  metadata?: {
    notes?: string;
  };
  created_at: string;
  updated_at: string;
};

export async function createEmployee(payload: CreateEmployeePayload): Promise<Employee> {
  return api.post<Employee>("/employees", payload);
}

export async function deleteEmployee(id: string): Promise<void> {
  return api.delete(`/employees/${id}`);
}
