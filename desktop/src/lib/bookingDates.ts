import type { DayCapacityDto } from "./types";

const MONTH_NAMES = ["", "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
const WEEK_DAYS = ["شنبه", "یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنجشنبه", "جمعه"];

function pad(n: number) {
  return n < 10 ? `0${n}` : String(n);
}

function jalaliToGregorian(jy: number, jm: number, jd: number) {
  let year = jy + 1595;
  let days = -355668 + 365 * year + Math.floor(year / 33) * 8 + Math.floor(((year % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
  let gy = 400 * Math.floor(days / 146097);
  days %= 146097;
  if (days > 36524) {
    gy += 100 * Math.floor(--days / 36524);
    days %= 36524;
    if (days >= 365) days += 1;
  }
  gy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) {
    gy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }
  let gd = days + 1;
  const sal = [0, 31, (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
  let gm = 1;
  while (gm <= 12 && gd > sal[gm]) {
    gd -= sal[gm];
    gm += 1;
  }
  return { y: gy, m: gm, d: gd };
}

export function todayJalaliKey() {
  const now = new Date();
  const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
  const gy = now.getFullYear();
  const gm = now.getMonth() + 1;
  const gd = now.getDate();
  const gy2 = gm > 2 ? gy + 1 : gy;
  let days = 355666 + 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
  let jy = -1595 + 33 * Math.floor(days / 12053);
  days %= 12053;
  jy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) {
    jy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }
  const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
  const jd = days < 186 ? 1 + (days % 31) : 1 + ((days - 186) % 30);
  return `${jy}/${pad(jm)}/${pad(jd)}`;
}

export function jalaliWeekday(key: string) {
  const [y, m, d] = key.split("/").map(Number);
  if (!y || !m || !d) return "";
  const g = jalaliToGregorian(y, m, d);
  const date = new Date(g.y, g.m - 1, g.d);
  return WEEK_DAYS[(date.getDay() + 1) % 7] || "";
}

export type DateOption = {
  value: string;
  label: string;
  full: boolean;
  disabled: boolean;
};

export function dateOptionsFromDays(
  days: Record<string, DayCapacityDto>,
  opts?: { showBooked?: boolean; keep?: string | null },
): DateOption[] {
  const today = todayJalaliKey();
  const showBooked = !!opts?.showBooked;
  const keep = opts?.keep || "";
  return Object.keys(days)
    .sort()
    .filter((key) => key >= today || key === keep)
    .map((key) => {
      const info = days[key] || {};
      const free = Math.max(0, Number(info.free || 0));
      const full = info.status === "full" || free <= 0;
      const [y, m, d] = key.split("/").map(Number);
      const weekday = jalaliWeekday(key);
      const labelDate = `${d} ${MONTH_NAMES[m] || m}${weekday ? ` ${weekday}` : ""}، ${y}`;
      return {
        value: key,
        full,
        disabled: full && !showBooked,
        label: full
          ? showBooked
            ? `${labelDate} (تکمیل — قابل انتخاب / استثنا)`
            : `${labelDate} (تکمیل)`
          : `${labelDate} (🟢 ${free} خالی)`,
      };
    });
}
