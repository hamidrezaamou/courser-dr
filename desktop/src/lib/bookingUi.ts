import type { BookingDto } from "./types";

export function boardTintClass(status?: string | null): string {
  switch (status) {
    case "confirmed":
      return "is-confirmed";
    case "waiting":
      return "is-queue";
    case "ready":
      return "is-ready";
    case "in_consult":
      return "is-consult";
    case "cancelled":
      return "is-cancelled";
    case "done":
      return "is-done";
    case "pending_approval":
      return "is-pending-approval";
    default:
      return "is-waiting";
  }
}

export function turnLabel(item: BookingDto, index: number): string {
  const time = item.scheduled_time_label || "";
  return /^نوبت\s*\d+/u.test(time) ? time : `نوبت ${index + 1}`;
}

export function kindLine(item: BookingDto): string {
  if (item.kind === "surgery") {
    const parts = [item.title || "عمل"];
    if (item.surgery_subtype_name && !(item.title || "").includes(item.surgery_subtype_name)) {
      parts.push(item.surgery_subtype_name);
    }
    if (item.eye_side_label && !parts.join(" ").includes(item.eye_side_label)) {
      parts.push(item.eye_side_label);
    }
    return parts.join(" · ");
  }
  return [item.title || "ویزیت", item.subtitle].filter((part) => part && String(part).trim()).join(" · ");
}

export function placeLine(item: BookingDto): string {
  return item.kind === "surgery" ? item.hospital_name || "—" : "کلینیک";
}

export function bookingMeta(item: BookingDto): string {
  const when = [item.scheduled_date_jalali, item.scheduled_time_label].filter(Boolean).join(" ");
  if (item.kind === "surgery") {
    return [kindLine(item), item.hospital_name, when].filter(Boolean).join(" · ");
  }
  return [kindLine(item), when].filter(Boolean).join(" · ");
}

export function normalizeMobile(mobile?: string | null): string {
  let digits = String(mobile || "").replace(/\D+/g, "");
  if (digits.startsWith("98") && digits.length === 12) digits = `0${digits.slice(2)}`;
  if (digits.startsWith("9") && digits.length === 10) digits = `0${digits}`;
  return digits;
}

export function telHref(mobile?: string | null): string | null {
  const digits = normalizeMobile(mobile);
  return /^09\d{9}$/.test(digits) ? `tel:+98${digits.slice(1)}` : null;
}

export function smsHref(mobile?: string | null, body?: string | null): string | null {
  const digits = normalizeMobile(mobile);
  if (!/^09\d{9}$/.test(digits)) return null;
  const target = `sms:+98${digits.slice(1)}`;
  return body ? `${target}?body=${encodeURIComponent(body)}` : target;
}

export function defaultSmsBody(item: BookingDto): string {
  if (item.sms_body) return item.sms_body;
  const name = item.patient_name || "بیمار";
  const date = item.scheduled_date_jalali || "";
  const time = item.scheduled_time_label || "";
  if (item.kind === "surgery") {
    return `${name} عزیز، نوبت عمل ${item.title || ""} شما برای ${date} در ${item.hospital_name || "بیمارستان"} (${time}) ثبت شد.`;
  }
  return `${name} عزیز، نوبت ویزیت شما برای ${date} ساعت ${time} ثبت شد.`;
}

export function weekdayLabel(item: BookingDto): string {
  if (item.weekday) return item.weekday;
  if (!item.scheduled_date) return "";
  const date = new Date(`${item.scheduled_date}T12:00:00`);
  if (Number.isNaN(date.getTime())) return "";
  return ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنجشنبه", "جمعه", "شنبه"][date.getDay()] || "";
}

export function copyText(value?: string | null) {
  const text = String(value || "").trim();
  if (!text) return;
  void navigator.clipboard.writeText(text);
}
