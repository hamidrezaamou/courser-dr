import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { PageChrome } from "./PageChrome";
import { ApiError } from "../lib/api";
import { normalizeMobile } from "../lib/bookingUi";
import {
  applyMessageTags,
  destroyReadyAnswer,
  duplicateReadyAnswer,
  loadReadyAnswers,
  loadReadyBookings,
  markReadyAnswerUsed,
  pinReadyAnswer,
  sendReadyAnswer,
  storeReadyAnswer,
  updateReadyAnswer,
  type ReadyAnswerDto,
  type ReadyChip,
} from "../lib/ops";
import type { BookingDto, Session } from "../lib/types";

export type ReadyTarget = {
  patientId?: number;
  patientName?: string | null;
  mobile?: string | null;
  nationalCode?: string | null;
  mobileSecondary?: string | null;
  extraVars?: Record<string, string>;
};

export type ReadyBooking = {
  kind?: string;
  id: number;
  label?: string;
  meta?: string;
  vars?: Record<string, string>;
};

const DEFAULT_CHIPS: { person: ReadyChip[]; booking: ReadyChip[] } = {
  person: [
    { token: "{نام}", label: "نام" },
    { token: "{موبایل}", label: "موبایل" },
    { token: "{موبایل۲}", label: "موبایل ۲" },
    { token: "{کدملی}", label: "کد ملی" },
    { token: "{امروز}", label: "امروز" },
    { token: "{مطب}", label: "مطب" },
  ],
  booking: [
    { token: "{تاریخ}", label: "تاریخ نوبت" },
    { token: "{ساعت}", label: "ساعت نوبت" },
    { token: "{نوبت}", label: "شماره نوبت" },
    { token: "{بیمارستان}", label: "بیمارستان" },
    { token: "{آدرس بیمارستان}", label: "آدرس بیمارستان" },
    { token: "{نوع عمل}", label: "نوع عمل" },
    { token: "{نوع ویزیت}", label: "نوع ویزیت" },
    { token: "{چشم}", label: "چشم" },
  ],
};

export function targetFromBooking(item: BookingDto): ReadyTarget {
  return {
    patientId: item.patient_id || undefined,
    patientName: item.patient_name,
    mobile: item.mobile,
    nationalCode: item.national_code,
    mobileSecondary: item.mobile_secondary,
    extraVars: {
      تاریخ: item.scheduled_date_jalali || "",
      ساعت: item.scheduled_time_label || "",
      نوبت: item.scheduled_time_label || "",
      بیمارستان: item.hospital_name || "",
      چشم: item.eye_side_label || "",
      "نوع عمل": item.kind === "surgery" ? (item.title || "") : "",
      "نوع ویزیت": item.kind === "visit" ? (item.title || "") : "",
    },
  };
}

function todayJalali() {
  return new Intl.DateTimeFormat("fa-IR-u-ca-persian", { year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date()).replace(/‏/g, "");
}

function faNum(value: number | string) {
  return String(value).replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[Number(digit)]);
}

function isUnicode(text: string) {
  return /[^\x00-\x7F]/.test(text || "");
}

function smsParts(text: string) {
  const len = (text || "").length;
  if (!len) return 0;
  const unicode = isUnicode(text);
  const single = unicode ? 70 : 160;
  const multi = unicode ? 67 : 153;
  return len <= single ? 1 : Math.ceil(len / multi);
}

function meterLabel(text: string) {
  const len = (text || "").length;
  if (!len) return "هنوز متنی نوشته نشده";
  const unicode = isUnicode(text);
  const parts = smsParts(text);
  const capacity = parts > 1 ? parts * (unicode ? 67 : 153) : (unicode ? 70 : 160);
  return `${faNum(len)} کاراکتر · ${faNum(capacity - len)} کاراکتر تا پیامک بعدی`;
}

function isLong(text?: string | null) {
  const value = text || "";
  return value.length > 170 || value.split("\n").length > 4;
}

function hasVars(text: string) {
  return /\{[^}]+\}/.test(text || "");
}

function mobileValid(value: string) {
  return /^09\d{9}$/.test(normalizeMobile(value));
}

function CloseIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
      <path strokeLinecap="round" d="M6 6l12 12M18 6L6 18" />
    </svg>
  );
}

export function ReadyAnswersPanel({
  session,
  target,
  onClose,
  variant = "overlay",
}: {
  session: Session;
  target?: ReadyTarget | null;
  onClose?: () => void;
  variant?: "overlay" | "page";
}) {
  const [tab, setTab] = useState<"list" | "compose" | "form">("list");
  const [q, setQ] = useState("");
  const [debouncedQ, setDebouncedQ] = useState("");
  const [category, setCategory] = useState("");
  const [sort, setSort] = useState("smart");
  const [items, setItems] = useState<ReadyAnswerDto[]>([]);
  const [categories, setCategories] = useState<string[]>([]);
  const [chips, setChips] = useState<{ person?: ReadyChip[]; booking?: ReadyChip[] }>(DEFAULT_CHIPS);
  const [smsEnabled, setSmsEnabled] = useState(false);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState("");
  const [msgKind, setMsgKind] = useState<"ok" | "err">("ok");
  const [mobile, setMobile] = useState(target?.mobile || "");
  const [bookings, setBookings] = useState<ReadyBooking[]>([]);
  const [booking, setBooking] = useState<ReadyBooking | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [pickerLoading, setPickerLoading] = useState(false);
  const [expanded, setExpanded] = useState<Record<number, boolean>>({});
  const [confirmId, setConfirmId] = useState<number | null>(null);
  const [copiedId, setCopiedId] = useState<number | "custom" | null>(null);
  const [sendingId, setSendingId] = useState<number | "custom" | null>(null);
  const [customMessage, setCustomMessage] = useState("");
  const [formTitle, setFormTitle] = useState("");
  const [formCategory, setFormCategory] = useState("");
  const [formBody, setFormBody] = useState("");
  const [formPinned, setFormPinned] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const customRef = useRef<HTMLTextAreaElement>(null);
  const formRef = useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    const timer = window.setTimeout(() => setDebouncedQ(q), 300);
    return () => window.clearTimeout(timer);
  }, [q]);

  useEffect(() => {
    setMobile(target?.mobile || "");
    if (target?.extraVars && Object.values(target.extraVars).some(Boolean)) {
      setBooking({
        id: 0,
        kind: target.extraVars["نوع عمل"] ? "surgery" : "visit",
        label: target.extraVars["نوع عمل"] || target.extraVars["نوع ویزیت"] || "نوبت انتخاب‌شده",
        meta: [target.extraVars["تاریخ"], target.extraVars["ساعت"]].filter(Boolean).join(" · "),
        vars: target.extraVars,
      });
    }
  }, [target]);

  const vars = useMemo(() => ({
    نام: target?.patientName || "",
    موبایل: normalizeMobile(mobile) || mobile,
    "موبایل۲": target?.mobileSecondary || "",
    کدملی: target?.nationalCode || "",
    امروز: todayJalali(),
    مطب: session.clinic?.clinic_label || session.clinic?.name || "مطب",
    ...(booking?.vars || target?.extraVars || {}),
  }), [target, mobile, session.clinic, booking]);

  function resolve(body?: string | null) {
    return applyMessageTags(body || "", vars);
  }

  function flash(text: string, kind: "ok" | "err" = "ok") {
    setMsg(text);
    setMsgKind(kind);
  }

  async function refresh() {
    setLoading(true);
    try {
      const data = await loadReadyAnswers(session, debouncedQ, category, sort);
      setItems(data.items || []);
      setCategories(data.categories || []);
      setChips({
        person: data.chips?.person?.length ? data.chips.person : DEFAULT_CHIPS.person,
        booking: data.chips?.booking?.length ? data.chips.booking : DEFAULT_CHIPS.booking,
      });
      setSmsEnabled(!!data.sms_enabled);
    } catch (err) {
      flash(err instanceof ApiError ? err.message : "پاسخ‌ها بارگذاری نشد.", "err");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void refresh();
  }, [session, debouncedQ, category, sort]);

  async function openPicker() {
    if (!target?.patientId) {
      flash("ابتدا بیمار را مشخص کنید.", "err");
      return;
    }
    setPickerOpen(true);
    setPickerLoading(true);
    try {
      const data = await loadReadyBookings(session, target.patientId);
      setBookings(data.data || []);
    } catch {
      setBookings([]);
    } finally {
      setPickerLoading(false);
    }
  }

  function insertToken(which: "custom" | "form", token: string, needsBooking?: boolean) {
    if (needsBooking && !booking && target?.patientId) {
      void openPicker();
      return;
    }
    const setter = which === "custom" ? setCustomMessage : setFormBody;
    const node = which === "custom" ? customRef.current : formRef.current;
    setter((current) => {
      if (!node) return current + token;
      const start = node.selectionStart ?? current.length;
      const end = node.selectionEnd ?? current.length;
      const next = current.slice(0, start) + token + current.slice(end);
      window.setTimeout(() => {
        node.focus();
        const caret = start + token.length;
        node.setSelectionRange(caret, caret);
      }, 0);
      return next;
    });
  }

  async function copyText(text: string, id: number | "custom") {
    await navigator.clipboard.writeText(text);
    setCopiedId(id);
    window.setTimeout(() => setCopiedId((current) => (current === id ? null : current)), 1400);
    if (typeof id === "number") void markReadyAnswerUsed(session, id).then(refresh).catch(() => undefined);
  }

  async function shareText(text: string) {
    try {
      if (navigator.share) {
        await navigator.share({ text });
        return;
      }
    } catch {
      /* fall through */
    }
    await navigator.clipboard.writeText(text);
    flash("متن کپی شد.");
  }

  async function sendMessage(template: string, answerId?: number) {
    const text = resolve(template).trim();
    if (!text) {
      flash("متن پیامک خالی است.", "err");
      return;
    }
    if (!mobileValid(mobile)) {
      flash("شماره مقصد را درست وارد کنید (مثل 09123456789).", "err");
      return;
    }
    if (!smsEnabled) {
      flash("ارسال پیامک در تنظیمات غیرفعال است. کپی و اشتراک‌گذاری کار می‌کند.", "err");
      return;
    }
    setBusy(true);
    setSendingId(answerId ?? "custom");
    try {
      const data = await sendReadyAnswer(session, {
        mobile: normalizeMobile(mobile),
        message: text,
        answer_id: answerId,
      });
      flash(data.message || "پیامک ارسال شد.");
      if (answerId) await refresh();
    } catch (err) {
      flash(err instanceof ApiError ? err.message : "ارسال نشد.", "err");
    } finally {
      setBusy(false);
      setSendingId(null);
    }
  }

  function startEdit(item: ReadyAnswerDto) {
    setEditingId(item.id);
    setFormTitle(item.title || "");
    setFormCategory(item.category || "");
    setFormBody(item.body || "");
    setFormPinned(!!item.is_pinned);
    setTab("form");
  }

  function resetForm() {
    setEditingId(null);
    setFormTitle("");
    setFormCategory("");
    setFormBody("");
    setFormPinned(false);
  }

  async function saveForm() {
    const body = formBody.trim();
    if (!body || busy) return;
    setBusy(true);
    try {
      const payload = { title: formTitle, category: formCategory, body, is_pinned: formPinned };
      if (editingId) await updateReadyAnswer(session, editingId, payload);
      else await storeReadyAnswer(session, payload);
      flash(editingId ? "پاسخ ویرایش شد." : "پاسخ ذخیره شد.");
      resetForm();
      setTab("list");
      await refresh();
    } catch (err) {
      flash(err instanceof ApiError ? err.message : "ذخیره نشد.", "err");
    } finally {
      setBusy(false);
    }
  }

  const personChips = chips.person?.length ? chips.person : DEFAULT_CHIPS.person;
  const bookingChips = chips.booking?.length ? chips.booking : DEFAULT_CHIPS.booking;
  const filtered = !!(debouncedQ || category || sort === "used");
  const countLabel = items.length ? `${faNum(items.length)} متن آماده برای ارسال` : "کتابخانه متن‌های پرتکرار";
  const subtitle = !smsEnabled ? "کتابخانه متن‌های پرتکرار" : countLabel;
  const mobileState = !mobile.trim() ? "" : mobileValid(mobile) ? "is-valid" : "is-invalid";

  const sheet = (
    <div className="ans-sheet" role="dialog" aria-modal={variant === "overlay"} aria-labelledby="ans-title" onClick={(event) => event.stopPropagation()}>
      <header className="ans-head">
        <span className="ans-head__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
          </svg>
        </span>
        <div className="ans-head__text">
          <h3 className="ans-head__title" id="ans-title">پاسخ‌های آماده</h3>
          <p className="ans-head__sub">{subtitle}</p>
        </div>
        {variant === "overlay" && onClose ? (
          <button type="button" className="ans-close" onClick={onClose} aria-label="بستن پنل">
            <CloseIcon />
          </button>
        ) : null}
      </header>

      <div className="ans-recipient">
        <div className="ans-recip__row">
          {target?.patientName ? (
            <span className="ans-patient">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" />
              </svg>
              <span>{target.patientName}</span>
            </span>
          ) : null}
          <div className="ans-recip__field">
            <input
              className="ans-input"
              type="tel"
              dir="ltr"
              inputMode="numeric"
              value={mobile}
              onChange={(event) => setMobile(event.target.value)}
              placeholder="09123456789"
              aria-label="شماره مقصد"
            />
            <span
              className={`ans-recip__dot ${mobileState}`}
              title={!mobile.trim() ? "شماره وارد نشده" : mobileValid(mobile) ? "شماره معتبر است" : "شماره نامعتبر"}
            />
          </div>
        </div>

        <div className="ans-booking">
          <button type="button" className="ans-booking__chip" onClick={() => void openPicker()} disabled={!target?.patientId}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6.75h15A1.5 1.5 0 0121 8.25v10.5a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18.75V8.25a1.5 1.5 0 011.5-1.5z" />
            </svg>
            <span className="ans-booking__text">
              <strong>{booking ? booking.label : "انتخاب عمل یا ویزیت"}</strong>
              {booking?.meta ? <small>{booking.meta}</small> : null}
              {!booking && target?.patientId ? <small>برای پر کردن تگ‌های نوبت، یک مورد را انتخاب کنید</small> : null}
            </span>
          </button>
          {booking && target?.patientId ? (
            <button type="button" className="ans-booking__clear" onClick={() => setBooking(null)} title="حذف انتخاب">×</button>
          ) : null}
        </div>

        <div className="ans-tabs" role="tablist">
          <button type="button" className={tab === "list" ? "ans-tab is-active" : "ans-tab"} role="tab" aria-selected={tab === "list"} onClick={() => setTab("list")}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" />
            </svg>
            فهرست
          </button>
          <button type="button" className={tab === "compose" ? "ans-tab is-active" : "ans-tab"} role="tab" aria-selected={tab === "compose"} onClick={() => setTab("compose")}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 12h12M12 6v12" />
            </svg>
            پیام دلخواه
          </button>
          <button type="button" className={tab === "form" ? "ans-tab is-active" : "ans-tab"} role="tab" aria-selected={tab === "form"} onClick={() => setTab("form")}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" d="M16.86 4.49l2.65 2.65M4 20l.9-3.6L15.4 5.9a1.4 1.4 0 012 0l.7.7a1.4 1.4 0 010 2L7.6 19.1 4 20z" />
            </svg>
            <span>{editingId ? "ویرایش" : "پاسخ جدید"}</span>
          </button>
        </div>

        {msg ? (
          <p className={`ans-alert ${msgKind === "err" ? "ans-alert--err" : "ans-alert--ok"}`}>
            <span>{msg}</span>
          </p>
        ) : null}
        {!smsEnabled ? (
          <p className="ans-alert ans-alert--warn">
            <span>ارسال پیامک در تنظیمات غیرفعال است. کپی و اشتراک‌گذاری کار می‌کند.</span>
          </p>
        ) : null}
      </div>

      <div className="ans-scroll">
        {tab === "list" ? (
          <>
            <div className="ans-search">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
              </svg>
              <input type="search" className="ans-input" value={q} onChange={(event) => setQ(event.target.value)} placeholder="جستجو در عنوان، متن یا دسته..." aria-label="جستجوی پاسخ‌ها" />
            </div>
            <div className="ans-filters">
              <button type="button" className={!category && sort !== "used" ? "ans-chip is-active" : "ans-chip"} onClick={() => { setCategory(""); setSort("smart"); }}>همه</button>
              <button type="button" className={sort === "used" ? "ans-chip is-active" : "ans-chip"} onClick={() => setSort("used")}>
                پرکاربردها
              </button>
              {categories.map((cat) => (
                <button key={cat} type="button" className={category === cat ? "ans-chip is-active" : "ans-chip"} onClick={() => setCategory(category === cat ? "" : cat)}>{cat}</button>
              ))}
            </div>
            {loading ? (
              <div className="ans-skeleton">
                <div className="ans-skeleton__row" />
                <div className="ans-skeleton__row" />
                <div className="ans-skeleton__row" />
              </div>
            ) : null}
            {!loading ? items.map((item) => {
              const resolved = resolve(item.body);
              return (
                <article key={item.id} className={`ans-item${item.is_pinned ? " is-pinned" : ""}`}>
                  <div className="ans-item__top">
                    <button
                      type="button"
                      className={`ans-pin${item.is_pinned ? " is-on" : ""}`}
                      onClick={() => void pinReadyAnswer(session, item.id).then(refresh)}
                      title={item.is_pinned ? "برداشتن سنجاق" : "سنجاق به بالای فهرست"}
                    >
                      <svg viewBox="0 0 24 24" fill={item.is_pinned ? "currentColor" : "none"} stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3l2.4 5.3 5.6.6-4.2 3.9 1.2 5.7L12 15.7 7 18.5l1.2-5.7L4 8.9l5.6-.6L12 3z" />
                      </svg>
                    </button>
                    <span className={`ans-item__label${!item.title ? " ans-item__label--empty" : ""}`}>{item.title || "بدون عنوان"}</span>
                    {item.category ? <span className="ans-tag">{item.category}</span> : null}
                    {(item.usage_count || 0) > 0 ? (
                      <span className="ans-uses" title={`تا کنون ${item.usage_count} بار استفاده شده`}>
                        <span>{faNum(item.usage_count || 0)}</span>
                      </span>
                    ) : null}
                  </div>
                  <div className={`ans-item__body${!expanded[item.id] && isLong(item.body) ? " is-clamped" : ""}`}>{resolved}</div>
                  {isLong(item.body) ? (
                    <button type="button" className="ans-more" onClick={() => setExpanded((current) => ({ ...current, [item.id]: !current[item.id] }))}>
                      {expanded[item.id] ? "کمتر" : "نمایش کامل"}
                    </button>
                  ) : null}
                  <div className="ans-meter">
                    <span>{meterLabel(resolved)}</span>
                    <span className={`ans-meter__parts${smsParts(resolved) > 2 ? " is-high" : ""}`}>{faNum(smsParts(resolved))} پیامک</span>
                  </div>
                  <div className="ans-acts">
                    <button type="button" className="ans-act ans-act--send" disabled={busy || !smsEnabled} onClick={() => void sendMessage(item.body || "", item.id)}>
                      <span>{sendingId === item.id ? "در حال ارسال..." : "ارسال"}</span>
                    </button>
                    <button type="button" className={`ans-act${copiedId === item.id ? " is-done" : ""}`} onClick={() => void copyText(resolved, item.id)} title="کپی متن">
                      <span>{copiedId === item.id ? "کپی شد" : "کپی"}</span>
                    </button>
                    <button type="button" className="ans-act ans-act--icon" onClick={() => void shareText(resolved)} title="اشتراک‌گذاری" aria-label="اشتراک‌گذاری">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M7.2 13.4l9.6 4.8M16.8 5.8L7.2 10.6M18 8a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM6 14.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM18 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                      </svg>
                    </button>
                    <button type="button" className="ans-act ans-act--icon" onClick={() => startEdit(item)} title="ویرایش" aria-label="ویرایش">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.86 4.49l2.65 2.65M4 20l.9-3.6L15.4 5.9a1.4 1.4 0 012 0l.7.7a1.4 1.4 0 010 2L7.6 19.1 4 20z" />
                      </svg>
                    </button>
                    <button type="button" className="ans-act ans-act--icon" onClick={() => void duplicateReadyAnswer(session, item.id).then(refresh)} title="ساخت رونوشت" aria-label="ساخت رونوشت">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M8 4h9a3 3 0 013 3v9M6 8h9a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a2 2 0 012-2z" />
                      </svg>
                    </button>
                    <button type="button" className="ans-act ans-act--icon ans-act--danger" onClick={() => setConfirmId(confirmId === item.id ? null : item.id)} title="حذف" aria-label="حذف">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 7h14M10 7V5.5c0-.6.4-1 1-1h2c.6 0 1 .4 1 1V7m-7 0l.8 12c0 .6.5 1 1 1h6.4c.5 0 1-.4 1-1L18 7" />
                      </svg>
                    </button>
                  </div>
                  {confirmId === item.id ? (
                    <div className="ans-confirm">
                      <p>این پاسخ حذف شود؟</p>
                      <button type="button" className="ans-confirm__yes" disabled={busy} onClick={() => void destroyReadyAnswer(session, item.id).then(() => { setConfirmId(null); return refresh(); })}>بله، حذف کن</button>
                      <button type="button" className="ans-confirm__no" onClick={() => setConfirmId(null)}>انصراف</button>
                    </div>
                  ) : null}
                </article>
              );
            }) : null}
            {!loading && items.length === 0 ? (
              <div className="ans-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <p className="ans-empty__title">{filtered ? "چیزی پیدا نشد" : "هنوز پاسخ آماده‌ای ندارید"}</p>
                <p className="ans-empty__text">{filtered ? "عبارت جستجو یا دسته را تغییر دهید." : "متن‌هایی که زیاد برای بیماران می‌فرستید را یک بار ذخیره کنید تا همیشه یک کلیک فاصله داشته باشند."}</p>
                {filtered ? (
                  <button type="button" className="ans-btn ans-btn--ghost" style={{ width: "auto" }} onClick={() => { setQ(""); setCategory(""); setSort("smart"); }}>پاک کردن فیلترها</button>
                ) : (
                  <button type="button" className="ans-btn" style={{ width: "auto" }} onClick={() => { resetForm(); setTab("form"); }}>
                    ساخت اولین پاسخ
                  </button>
                )}
              </div>
            ) : null}
          </>
        ) : null}

        {tab === "compose" ? (
          <div className="ans-card">
            <h4 className="ans-card__title">ارسال پیامک دلخواه</h4>
            <textarea className="ans-input" rows={4} ref={customRef} value={customMessage} onChange={(event) => setCustomMessage(event.target.value)} placeholder="متن پیامک را بنویسید..." maxLength={1000} aria-label="متن پیامک دلخواه" />
            <div className="ans-vars">
              <span className="ans-vars__hint">بیمار:</span>
              {personChips.map((chip) => (
                <button key={chip.token} type="button" className="ans-var" title={chip.label} onClick={() => insertToken("custom", chip.token)}>{chip.label}</button>
              ))}
            </div>
            <div className="ans-vars">
              <span className="ans-vars__hint">نوبت:</span>
              {bookingChips.map((chip) => (
                <button key={chip.token} type="button" className={`ans-var ans-var--booking${!booking ? " needs-pick" : ""}`} title={chip.label} onClick={() => insertToken("custom", chip.token, true)}>{chip.label}</button>
              ))}
            </div>
            <div className="ans-meter">
              <span>{meterLabel(resolve(customMessage))}</span>
              <span className={`ans-meter__parts${smsParts(resolve(customMessage)) > 2 ? " is-high" : ""}`}>{faNum(smsParts(resolve(customMessage)))} پیامک</span>
            </div>
            {hasVars(customMessage) ? (
              <div className="ans-preview">
                <span className="ans-preview__cap">پیش‌نمایش متن نهایی</span>
                <span>{resolve(customMessage)}</span>
              </div>
            ) : null}
            <div className="ans-btn-row">
              <button type="button" className="ans-btn" disabled={busy || !smsEnabled || !customMessage.trim()} onClick={() => void sendMessage(customMessage)}>
                <span>{busy && sendingId === "custom" ? "در حال ارسال..." : "ارسال پیامک"}</span>
              </button>
              <button
                type="button"
                className="ans-btn ans-btn--ghost"
                style={{ width: "auto" }}
                disabled={!customMessage.trim()}
                onClick={() => { resetForm(); setFormBody(customMessage); setTab("form"); }}
              >
                ذخیره در فهرست
              </button>
            </div>
          </div>
        ) : null}

        {tab === "form" ? (
          <div className="ans-card">
            <h4 className="ans-card__title">{editingId ? "ویرایش پاسخ آماده" : "افزودن پاسخ آماده"}</h4>
            <label className="ans-label" htmlFor="ans-form-title">عنوان (اختیاری)</label>
            <input id="ans-form-title" className="ans-input" type="text" value={formTitle} onChange={(event) => setFormTitle(event.target.value)} placeholder="مثلاً: آماده‌سازی قبل از عمل" maxLength={120} />
            <label className="ans-label" style={{ marginTop: "0.5rem" }} htmlFor="ans-form-cat">دسته (اختیاری)</label>
            <input id="ans-form-cat" className="ans-input" type="text" value={formCategory} onChange={(event) => setFormCategory(event.target.value)} list="ans-categories" placeholder="مثلاً: نوبت‌دهی" maxLength={60} />
            <datalist id="ans-categories">
              {categories.map((cat) => <option key={cat} value={cat} />)}
            </datalist>
            <label className="ans-label" style={{ marginTop: "0.5rem" }} htmlFor="ans-form-body">متن پاسخ</label>
            <textarea id="ans-form-body" className="ans-input" rows={5} ref={formRef} value={formBody} onChange={(event) => setFormBody(event.target.value)} placeholder="متن پاسخ را بنویسید..." maxLength={2000} />
            <div className="ans-vars">
              <span className="ans-vars__hint">بیمار:</span>
              {personChips.map((chip) => (
                <button key={`f-${chip.token}`} type="button" className="ans-var" title={chip.label} onClick={() => insertToken("form", chip.token)}>{chip.label}</button>
              ))}
            </div>
            <div className="ans-vars">
              <span className="ans-vars__hint">نوبت:</span>
              {bookingChips.map((chip) => (
                <button key={`fb-${chip.token}`} type="button" className={`ans-var ans-var--booking${!booking ? " needs-pick" : ""}`} title={chip.label} onClick={() => insertToken("form", chip.token, true)}>{chip.label}</button>
              ))}
            </div>
            <div className="ans-meter">
              <span>{meterLabel(resolve(formBody))}</span>
              <span className={`ans-meter__parts${smsParts(resolve(formBody)) > 2 ? " is-high" : ""}`}>{faNum(smsParts(resolve(formBody)))} پیامک</span>
            </div>
            {hasVars(formBody) ? (
              <div className="ans-preview">
                <span className="ans-preview__cap">پیش‌نمایش متن نهایی</span>
                <span>{resolve(formBody)}</span>
              </div>
            ) : null}
            <label className={`ans-chip${formPinned ? " is-active" : ""}`} style={{ marginTop: "0.55rem" }}>
              <input type="checkbox" checked={formPinned} onChange={(event) => setFormPinned(event.target.checked)} style={{ display: "none" }} />
              سنجاق به بالای فهرست
            </label>
            <div className="ans-btn-row">
              <button type="button" className="ans-btn" disabled={busy || !formBody.trim()} onClick={() => void saveForm()}>
                <span>{busy ? "در حال ذخیره..." : (editingId ? "ذخیره تغییرات" : "ذخیره پاسخ")}</span>
              </button>
              <button type="button" className="ans-btn ans-btn--ghost" style={{ width: "auto" }} onClick={() => { resetForm(); setTab("list"); }}>انصراف</button>
            </div>
          </div>
        ) : null}
      </div>

      {pickerOpen ? (
        <div className="ans-picker" onClick={() => setPickerOpen(false)}>
          <div className="ans-picker__sheet" role="dialog" aria-modal="true" aria-labelledby="ans-picker-title" onClick={(event) => event.stopPropagation()}>
            <header className="ans-picker__head">
              <h4 id="ans-picker-title">انتخاب عمل یا ویزیت</h4>
              <button type="button" className="ans-close" onClick={() => setPickerOpen(false)} aria-label="بستن">
                <CloseIcon />
              </button>
            </header>
            <p className="ans-picker__hint">با انتخاب، تگ‌های تاریخ، ساعت، بیمارستان و نوع عمل از همین نوبت پر می‌شوند.</p>
            <div className="ans-picker__list">
              {pickerLoading ? (
                <div className="ans-skeleton">
                  <div className="ans-skeleton__row" />
                  <div className="ans-skeleton__row" />
                </div>
              ) : null}
              {!pickerLoading && bookings.length === 0 ? <p className="ans-picker__empty">عمل یا ویزیتی برای این بیمار ثبت نشده.</p> : null}
              {bookings.map((row) => (
                <button
                  key={`${row.kind}-${row.id}`}
                  type="button"
                  className={`ans-picker__item${booking && booking.kind === row.kind && booking.id === row.id ? " is-active" : ""}`}
                  onClick={() => { setBooking(row); setPickerOpen(false); }}
                >
                  <span className={`ans-picker__kind ${row.kind === "surgery" ? "is-surgery" : "is-visit"}`}>{row.kind === "surgery" ? "عمل" : "ویزیت"}</span>
                  <span className="ans-picker__item-text">
                    <strong>{row.label}</strong>
                    <small>{row.meta}</small>
                  </span>
                </button>
              ))}
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );

  const overlay = (
    <div className={variant === "page" ? "ans-overlay ans-overlay--page" : "ans-overlay"} onClick={variant === "overlay" ? onClose : undefined}>
      {sheet}
    </div>
  );

  if (variant === "page") return overlay;
  return createPortal(overlay, document.body);
}

export function ReadyAnswersScreen({ session }: { session: Session }) {
  return (
    <PageChrome
      bodyClass="dash-page"
      padded
      header={
        <div className="dash-header">
          <h2 className="dash-header__title">پاسخ‌های آماده</h2>
        </div>
      }
    >
      <ReadyAnswersPanel session={session} variant="page" />
    </PageChrome>
  );
}
