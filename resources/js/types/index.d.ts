export interface User {
  id: string;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
}

export interface PageProps {
  auth: {
    user: User | null;
  };
  flash: {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
  };
  errors: Record<string, string>;
  [key: string]: unknown;
}

export interface Paginator<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface Client {
  id: string;
  name: string;
  type: 'pf' | 'pj';
  document?: string | null;
  email?: string | null;
  phone?: string | null;
  contact_name?: string | null;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  zip?: string | null;
  notes?: string | null;
  status: 'ativo' | 'inativo';
  created_at: string;
}

export interface Opportunity {
  id: string;
  title: string;
  client_id?: string | null;
  contact_name?: string | null;
  responsible_id?: string | null;
  service_type?: string | null;
  description?: string | null;
  estimated_value?: string | number | null;
  negotiated_value?: string | number | null;
  opened_at?: string | null;
  expected_close_at?: string | null;
  status: string;
  notes?: string | null;
  created_at: string;
  client?: Client | null;
  responsible?: { id: string; name: string } | null;
}
