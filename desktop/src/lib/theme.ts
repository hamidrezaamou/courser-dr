const KEY = "theme";

export function isDark(): boolean {
  return document.documentElement.classList.contains("dark");
}

export function initTheme(): void {
  const stored = localStorage.getItem(KEY);
  const dark = stored === "dark" || (!stored && window.matchMedia("(prefers-color-scheme: dark)").matches);
  document.documentElement.classList.toggle("dark", dark);
}

export function applyTheme(dark: boolean): void {
  document.documentElement.classList.toggle("dark", dark);
  localStorage.setItem(KEY, dark ? "dark" : "light");
}

export function toggleTheme(): boolean {
  const next = !isDark();
  applyTheme(next);
  return next;
}
