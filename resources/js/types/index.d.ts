export interface User {
  id: string;
  name: string;
  email: string;
  roles: string[];
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
