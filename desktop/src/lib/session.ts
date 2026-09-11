import type { Session } from "./types";

const KEY = "mramo-desktop-session";
export const DEFAULT_SITE = "https://mramo.ir";

export function normalizeSite(raw: string): string {
  const trimmed = raw.trim().replace(/\/+$/, "");
  if (!trimmed) return DEFAULT_SITE;
  if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) return trimmed;
  return `https://${trimmed}`;
}

export function loadSession(): Session | null {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Session;
    if (!parsed?.token || !parsed?.site || !parsed?.user) return null;
    return { ...parsed, site: normalizeSite(parsed.site) };
  } catch {
    return null;
  }
}

export function saveSession(session: Session): void {
  localStorage.setItem(
    KEY,
    JSON.stringify({ ...session, site: normalizeSite(session.site) }),
  );
}

export function clearSession(): void {
  localStorage.removeItem(KEY);
}

export function digitsOnly(value: string): string {
  const mapped = value
    .replace(/[۰-۹]/g, (digit) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String("٠١٢٣٤٥٦٧٨٩".indexOf(digit)));
  return mapped.replace(/\D/g, "");
}
