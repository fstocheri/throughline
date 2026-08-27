import { apiClient } from './client';
import type { AuthUser } from './types';

interface LoginResponse {
  token: string;
  user: AuthUser;
}

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await apiClient.post<LoginResponse>('/login', { email, password });

  return data;
}

export async function logout(): Promise<void> {
  await apiClient.post('/logout');
}

export async function fetchCurrentUser(): Promise<AuthUser> {
  const { data } = await apiClient.get<AuthUser>('/user');

  return data;
}
