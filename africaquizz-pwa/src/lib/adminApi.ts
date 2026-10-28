import axios from "axios";

const BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, "") || "http://127.0.0.1:8000";
export const ADMIN_TOKEN_KEY = "aq.admin.token";

export const adminApi = axios.create({
  baseURL: `${BASE}/api/v1/admin`,
  headers: { Accept: "application/json", "Content-Type": "application/json" },
  timeout: 15000,
});

adminApi.interceptors.request.use((config) => {
  const token = sessionStorage.getItem(ADMIN_TOKEN_KEY);
  if (token && !config.headers.Authorization) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export function adminErrorMessage(error: unknown, fallback: string): string {
  if (axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(error)) {
    const body = error.response?.data;
    const firstFieldError = body?.errors ? Object.values(body.errors).flat()[0] : undefined;
    return firstFieldError || body?.message || fallback;
  }
  return fallback;
}
