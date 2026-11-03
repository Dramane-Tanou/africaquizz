import axios from "axios";
import i18n from "../i18n";
import { API_BASE } from "./api";

const BASE = API_BASE;
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
    if (!error.response) return i18n.t("admin.networkError");
    const body = error.response.data;
    const firstFieldError = body?.errors ? Object.values(body.errors).flat()[0] : undefined;
    return firstFieldError || body?.message || fallback;
  }
  return fallback;
}
