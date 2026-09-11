import { normalizeSite } from "./session";
import type { Session } from "./types";

export class ApiError extends Error {
  status: number;
  constructor(message: string, status = 0) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

export function isNetworkError(error: unknown): boolean {
  return error instanceof ApiError && error.status === 0;
}

function isBrowserDev(): boolean {
  return typeof window !== "undefined" && window.location.port === "1420";
}

function endpoint(site: string, path: string): string {
  const clean = path.replace(/^\//, "");
  if (isBrowserDev()) return `/__clinic/${clean}`;
  return `${normalizeSite(site)}/api/mobile/v1/${clean}`;
}

function firstError(raw: string): string | null {
  try {
    const tree = JSON.parse(raw) as {
      message?: string;
      errors?: Record<string, string[]>;
    };
    if (tree.message && tree.message !== "The given data was invalid.") {
      return tree.message;
    }
    const errors = tree.errors;
    if (errors) {
      const first = Object.values(errors)[0]?.[0];
      if (first) return first;
    }
    return tree.message ?? null;
  } catch {
    return null;
  }
}

export async function clinicRequest<T>(
  session: Pick<Session, "site" | "token">,
  method: string,
  path: string,
  body?: unknown,
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
    "X-Clinic-Site": normalizeSite(session.site),
  };
  if (session.token) headers.Authorization = `Bearer ${session.token}`;
  const isForm = typeof FormData !== "undefined" && body instanceof FormData;
  if (body !== undefined && !isForm) headers["Content-Type"] = "application/json";

  let response: Response;
  try {
    response = await fetch(endpoint(session.site, path), {
      method,
      headers,
      body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
    });
  } catch {
    throw new ApiError("اینترنت یا آدرس سایت را بررسی کنید.", 0);
  }

  const raw = await response.text();
  if (response.status === 401) {
    throw new ApiError("نشست منقضی شده. دوباره وارد شوید.", 401);
  }
  if (!response.ok) {
    throw new ApiError(firstError(raw) || `خطای ${response.status}`, response.status);
  }
  if (!raw) return {} as T;
  try {
    return JSON.parse(raw) as T;
  } catch {
    throw new ApiError("پاسخ سرور قابل خواندن نیست.", response.status);
  }
}

export async function clinicDownload(session: Pick<Session, "site" | "token">, path: string, filename: string) {
  const headers: Record<string, string> = {
    Accept: "*/*",
    "X-Requested-With": "XMLHttpRequest",
    "X-Clinic-Site": normalizeSite(session.site),
  };
  if (session.token) headers.Authorization = `Bearer ${session.token}`;
  let response: Response;
  try {
    response = await fetch(endpoint(session.site, path), { method: "GET", headers });
  } catch {
    throw new ApiError("اینترنت یا آدرس سایت را بررسی کنید.", 0);
  }
  if (!response.ok) {
    const raw = await response.text();
    throw new ApiError(firstError(raw) || `خطای ${response.status}`, response.status);
  }
  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
