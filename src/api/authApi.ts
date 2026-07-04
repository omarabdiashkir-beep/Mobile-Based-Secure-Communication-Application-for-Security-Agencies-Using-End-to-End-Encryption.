import { apiRequest } from './http';
import type { AuthSession } from '../navigation/types';

type ApiUser = {
  id: string;
  name: string;
  email: string;
  phone?: string;
  role_name?: string;
  status?: string;
};

type AuthResponse = {
  status: string;
  message: string;
  data: {
    token: string;
    token_type: string;
    must_change_password?: boolean;
    password_expired?: boolean;
    user: ApiUser;
  };
};

function mapAuthError(error: unknown): string {
  const message = error instanceof Error ? error.message : 'Network error';
  if (message.toLowerCase().includes('network request failed')) {
    return 'Cannot reach the API server. Check your internet connection.';
  }
  return message;
}

function mapToSession(data: AuthResponse['data']): AuthSession {
  return {
    token: data.token,
    userId: data.user.id,
    userName: data.user.name,
    email: data.user.email,
  };
}

export async function loginApi(
  email: string,
  password: string,
): Promise<{ session?: AuthSession; error?: string; mustChangePassword?: boolean; passwordExpired?: boolean }> {
  try {
    const response = await apiRequest<AuthResponse>('/api/auth/login', {
      method: 'POST',
      body: { email, password },
    });

    if (response?.status !== 'success' || !response?.data?.token) {
      return { error: response?.message || 'Login failed' };
    }

    return {
      session: mapToSession(response.data),
      mustChangePassword: response.data.must_change_password ?? false,
      passwordExpired: response.data.password_expired ?? false,
    };
  } catch (error) {
    return { error: mapAuthError(error) };
  }
}

export async function registerApi(payload: {
  name: string;
  email: string;
  password: string;
  phone?: string;
  address?: string;
  occupation?: string;
}): Promise<{ session?: AuthSession; error?: string; mustChangePassword?: boolean }> {
  try {
    const response = await apiRequest<AuthResponse>('/api/auth/register', {
      method: 'POST',
      body: payload,
    });

    if (response?.status !== 'success' || !response?.data?.token) {
      return { error: response?.message || 'Registration failed' };
    }

    return {
      session: mapToSession(response.data),
      mustChangePassword: response.data.must_change_password,
    };
  } catch (error) {
    return { error: mapAuthError(error) };
  }
}

export async function changePasswordApi(
  token: string,
  payload: {
    old_password?: string;  // omit for must_change_password flow
    password: string;
    password_confirm: string;
  },
): Promise<{ error?: string }> {
  try {
    const response = await apiRequest<{ status: string; message: string }>('/api/auth/change-password', {
      method: 'POST',
      token,
      body: payload,
    });

    if (response?.status !== 'success') {
      return { error: response?.message || 'Password change failed' };
    }

    return {};
  } catch (error) {
    return { error: mapAuthError(error) };
  }
}
