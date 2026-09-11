const PREFIX = "mramo-cache-v1:";
const LAST_SYNC = `${PREFIX}last-sync`;

type Envelope<T> = { at: number; data: T };

export function cacheGet<T>(key: string): T | null {
  try {
    const raw = localStorage.getItem(PREFIX + key);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Envelope<T>;
    return parsed?.data ?? null;
  } catch {
    return null;
  }
}

export function cacheSet<T>(key: string, data: T): void {
  const envelope: Envelope<T> = { at: Date.now(), data };
  localStorage.setItem(PREFIX + key, JSON.stringify(envelope));
  localStorage.setItem(LAST_SYNC, String(envelope.at));
}

export function cacheTime(key: string): number | null {
  try {
    const raw = localStorage.getItem(PREFIX + key);
    if (!raw) return null;
    return (JSON.parse(raw) as Envelope<unknown>).at ?? null;
  } catch {
    return null;
  }
}

export function lastSyncAt(): number | null {
  const raw = localStorage.getItem(LAST_SYNC);
  if (!raw) return null;
  const n = Number(raw);
  return Number.isFinite(n) ? n : null;
}

export function formatClock(at: number | null): string {
  if (!at) return "هنوز همگام نشده";
  return new Date(at).toLocaleTimeString("fa-IR", {
    hour: "2-digit",
    minute: "2-digit",
  });
}
