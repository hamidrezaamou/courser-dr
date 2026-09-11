import { useState } from "react";
import { createPortal } from "react-dom";
import { updatePatient } from "../lib/api";
import { enqueueStatus, flushOutbox } from "../lib/outbox";
import { bookingMeta, copyText, defaultSmsBody, smsHref, telHref } from "../lib/bookingUi";
import { sendReadyAnswer } from "../lib/ops";
import type { BookingDto, Session, StatusActionDto } from "../lib/types";

export type BookingNav = {
  onOpenPatient: (id: number) => void;
  onBookVisit?: (patientId?: number) => void;
  onBookSurgery?: (patientId?: number) => void;
  onPrints?: (surgeryId?: number) => void;
  onEdit?: (kind: "visit" | "surgery", id: number) => void;
};

function CloseIcon() {
  return (
    <svg className="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
      <path strokeLinecap="round" d="M6 6l12 12M18 6L6 18" />
    </svg>
  );
}

function actionClass(action: StatusActionDto) {
  const extra = action.id === "cancelled" || action.id === "no_show"
    ? " is-danger"
    : action.id === "confirmed" || action.id === "done"
      ? " is-ok"
      : "";
  return `rt-status-btn${extra}`;
}

export function RowToolbox({
  session,
  item,
  mode = "board",
  onClose,
  nav,
  onReady,
  onChecklist,
  onNotes,
  onApplied,
}: {
  session: Session;
  item: BookingDto;
  mode?: "board" | "report" | "floor";
  onClose: () => void;
  nav: BookingNav;
  onReady: () => void;
  onChecklist?: () => void;
  onNotes?: () => void;
  onApplied?: () => void;
}) {
  const [notice, setNotice] = useState<string | null>(null);
  const [statusOpen, setStatusOpen] = useState(false);
  const [pendingStatus, setPendingStatus] = useState<StatusActionDto | null>(null);
  const [smsOpen, setSmsOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [copied, setCopied] = useState<string | null>(null);
  const [mobile2, setMobile2] = useState(item.mobile_secondary || "");
  const smsBody = defaultSmsBody(item);
  const phone = telHref(item.mobile);
  const sms = smsHref(item.mobile, smsBody);
  const isPatient = item.kind === "patient" || item.id === 0;
  const canEditMobile2 = (isPatient || mode === "report") && !!item.patient_id && !!session.user.can_edit_patient;
  const canChangeStatus = !isPatient && !!item.actions?.length && !item.locked;
  const showSmsPreview = !isPatient && !!smsBody;

  function flashCopy(field: string, value?: string | null) {
    void copyText(value);
    setCopied(field);
    window.setTimeout(() => setCopied((current) => (current === field ? null : current)), 1200);
  }

  async function applyStatus(status: string) {
    setBusy(true);
    setNotice(null);
    try {
      enqueueStatus(item.kind || "visit", item.id, status, item.status, item.status_label);
      await flushOutbox(session);
      setPendingStatus(null);
      setStatusOpen(false);
      onApplied?.();
      onClose();
    } catch (err) {
      setNotice(err instanceof Error ? err.message : "وضعیت ذخیره نشد.");
    } finally {
      setBusy(false);
    }
  }

  async function sendPanelSms() {
    if (!item.mobile) {
      setNotice("شماره موبایل ثبت نشده است.");
      return;
    }
    setBusy(true);
    setNotice(null);
    try {
      const result = await sendReadyAnswer(session, { mobile: item.mobile, message: smsBody });
      setSmsOpen(false);
      setNotice(result.message || "پیامک ارسال شد.");
    } catch (err) {
      setNotice(err instanceof Error ? err.message : "ارسال پیامک پنل انجام نشد.");
      if (sms) window.open(sms, "_self");
    } finally {
      setBusy(false);
    }
  }

  async function saveMobile2() {
    const raw = mobile2.replace(/\D+/g, "").slice(0, 11);
    setMobile2(raw);
    if (!canEditMobile2 || !item.patient_id) return;
    if (raw === (item.mobile_secondary || "")) return;
    if (!item.patient_name || !item.national_code || !item.mobile) return;
    try {
      await updatePatient(session, item.patient_id, {
        name: item.patient_name,
        national_code: item.national_code,
        mobile: item.mobile,
        mobile_secondary: raw || undefined,
      });
      item.mobile_secondary = raw || null;
      setCopied("mobile2");
      window.setTimeout(() => setCopied((current) => (current === "mobile2" ? null : current)), 1200);
    } catch (err) {
      setMobile2(item.mobile_secondary || "");
      setNotice(err instanceof Error ? err.message : "ثبت شماره دوم ناموفق بود.");
    }
  }

  function go(fn: () => void) {
    fn();
    onClose();
  }

  const panelClass = `row-toolbox-panel${isPatient ? " row-toolbox-panel--patient" : ""}`;

  return createPortal(
    <>
      <div className="row-toolbox-overlay" onClick={onClose} role="presentation">
        <div className={panelClass} onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
          <button type="button" className="row-toolbox-close" onClick={onClose} aria-label="بستن">
            <CloseIcon />
          </button>
          <h4 className="row-toolbox-title">{item.patient_name || "جعبه ابزار"}</h4>

          <div className="row-toolbox-info">
            <div
              className={`row-toolbox-row is-copy${copied === "national" ? " is-copied" : ""}`}
              title="برای کپی کلیک کنید"
              onClick={() => flashCopy("national", item.national_code)}
            >
              <span className="lbl">کد ملی</span>
              <span className="val" dir="ltr">{item.national_code || "—"}</span>
            </div>
            <div
              className={`row-toolbox-row is-copy${copied === "mobile" ? " is-copied" : ""}`}
              title="برای کپی کلیک کنید"
              onClick={() => flashCopy("mobile", item.mobile)}
            >
              <span className="lbl">موبایل</span>
              <span className="val" dir="ltr">{item.mobile || "—"}</span>
            </div>
            {canEditMobile2 ? (
              <div className={`row-toolbox-row rt-phone2-row${copied === "mobile2" ? " is-copied" : ""}`} title="برای نوشتن شماره بزنید">
                <span className="lbl">موبایل دوم</span>
                <span className="val" dir="ltr">
                  <input
                    type="text"
                    className="rt-inline-phone"
                    dir="ltr"
                    inputMode="tel"
                    maxLength={11}
                    placeholder="—"
                    autoComplete="off"
                    aria-label="شماره تماس دوم"
                    value={mobile2}
                    onChange={(event) => setMobile2(event.target.value.replace(/\D+/g, "").slice(0, 11))}
                    onBlur={() => void saveMobile2()}
                  />
                </span>
              </div>
            ) : item.mobile_secondary ? (
              <div className="row-toolbox-row is-copy" onClick={() => flashCopy("mobile2", item.mobile_secondary)}>
                <span className="lbl">موبایل دوم</span>
                <span className="val" dir="ltr">{item.mobile_secondary}</span>
              </div>
            ) : null}
            <div className="row-toolbox-row">
              <span className="lbl">جزئیات</span>
              <span className="val">{isPatient ? (item.subtitle || "—") : bookingMeta(item)}</span>
            </div>
          </div>

          {showSmsPreview ? (
            <div className="row-toolbox-sms-preview">
              <span className="row-toolbox-sms-preview__label">متن پیامک نوبت</span>
              <p className="row-toolbox-sms-preview__text">{smsBody}</p>
            </div>
          ) : null}

          <div className="row-toolbox-actions">
            {phone ? (
              <a className="tb-btn" data-btn-style="soft" href={phone}>تماس</a>
            ) : (
              <span className="tb-btn is-disabled" data-btn-style="soft">تماس</span>
            )}
            {sms ? (
              <a className="tb-btn" data-btn-style="soft" href={sms}>پیامک</a>
            ) : (
              <span className="tb-btn is-disabled" data-btn-style="soft">پیامک</span>
            )}
            {!isPatient && nav.onEdit ? (
              <button type="button" className="tb-btn" data-btn-style="soft" onClick={() => go(() => nav.onEdit?.(item.kind === "surgery" ? "surgery" : "visit", item.id))}>
                ویرایش نوبت
              </button>
            ) : null}
            {canChangeStatus ? (
              <button type="button" className="tb-btn full" data-btn-style="soft" onClick={() => { setPendingStatus(null); setStatusOpen(true); }}>
                تغییر وضعیت
              </button>
            ) : null}
            {item.kind === "surgery" && nav.onPrints ? (
              <button type="button" className="tb-btn" data-btn-style="soft" onClick={() => go(() => nav.onPrints?.(item.id))}>
                چاپ برگه‌ها
              </button>
            ) : null}
            {item.kind === "surgery" && onChecklist ? (
              <button type="button" className="tb-btn full" data-btn-style="soft" onClick={() => go(() => onChecklist())}>
                چک‌لیست عمل
              </button>
            ) : null}
            {item.patient_id && nav.onBookSurgery ? (
              <button type="button" className="tb-btn" data-btn-style="soft" onClick={() => go(() => nav.onBookSurgery?.(item.patient_id || undefined))}>
                ثبت عمل
              </button>
            ) : null}
            {item.patient_id && nav.onBookVisit ? (
              <button type="button" className="tb-btn" data-btn-style="soft" onClick={() => go(() => nav.onBookVisit?.(item.patient_id || undefined))}>
                ثبت ویزیت
              </button>
            ) : null}
            <button type="button" className="tb-btn full" data-btn-style="soft" onClick={() => { onReady(); onClose(); }}>
              پاسخ‌های آماده
            </button>
            {item.mobile ? (
              <button type="button" className="tb-btn full" data-btn-style="soft" disabled={busy} onClick={() => setSmsOpen(true)}>
                ارسال پیامک از پنل SMS.ir
              </button>
            ) : null}
            {item.patient_id ? (
              <button type="button" className="tb-btn full" data-btn-style="soft" onClick={() => go(() => nav.onOpenPatient(item.patient_id!))}>
                پرونده بیمار
              </button>
            ) : null}
            {mode === "report" && onNotes ? (
              <button type="button" className="tb-btn full" data-btn-style="soft" onClick={() => go(() => onNotes())}>
                افزودن توضیحات
              </button>
            ) : null}
          </div>
          {notice ? <p className="row-toolbox-msg">{notice}</p> : null}
        </div>
      </div>

      {statusOpen ? (
        <div className="rt-status-overlay is-open" onClick={() => { setStatusOpen(false); setPendingStatus(null); }} role="presentation">
          <div className="rt-status-sheet" role="dialog" aria-modal="true" aria-label="تغییر وضعیت" onClick={(event) => event.stopPropagation()}>
            <div className="rt-status-sheet__head">
              <div>
                <strong>تغییر وضعیت</strong>
                <p>وضعیت فعلی: {item.status_label || "—"}</p>
              </div>
              <button type="button" className="tg-tool" onClick={() => { setStatusOpen(false); setPendingStatus(null); }} aria-label="بستن">
                <CloseIcon />
              </button>
            </div>
            {!pendingStatus ? (
              <div className="rt-status-sheet__list">
                {(item.actions || []).length === 0 ? (
                  <p className="rt-status-sheet__empty">برای این نوبت تغییر وضعیت مجاز نیست.</p>
                ) : (
                  item.actions?.map((action) => (
                    <button
                      key={action.id || action.label || ""}
                      type="button"
                      className={actionClass(action)}
                      onClick={() => setPendingStatus(action)}
                    >
                      {action.label}
                    </button>
                  ))
                )}
              </div>
            ) : (
              <div className="rt-status-sheet__confirm">
                <h4>{pendingStatus.label}</h4>
                <p>{pendingStatus.hint || "این تغییر اعمال شود؟"}</p>
                <div className="rt-status-sheet__confirm-acts">
                  <button type="button" className="btn-secondary !py-1.5 !px-3 !text-xs" onClick={() => setPendingStatus(null)}>انصراف</button>
                  <button type="button" className="btn-primary !py-1.5 !px-3 !text-xs" disabled={busy} onClick={() => void applyStatus(pendingStatus.id || "")}>
                    بله، انجام بده
                  </button>
                </div>
              </div>
            )}
            {notice ? <p className="rt-status-sheet__msg">{notice}</p> : null}
          </div>
        </div>
      ) : null}

      {smsOpen ? (
        <div className="rt-sms-confirm-overlay" onClick={() => setSmsOpen(false)} role="presentation">
          <div className="rt-sms-confirm" role="dialog" aria-modal="true" aria-label="تأیید ارسال پیامک" onClick={(event) => event.stopPropagation()}>
            <h4 className="rt-sms-confirm__title">ارسال پیامک؟</h4>
            <p className="rt-sms-confirm__to">به شماره <strong dir="ltr">{item.mobile}</strong></p>
            <div className="rt-sms-confirm__body">{smsBody}</div>
            <div className="rt-sms-confirm__actions">
              <button type="button" className="rt-sms-confirm__cancel" onClick={() => setSmsOpen(false)}>انصراف</button>
              <button type="button" className="rt-sms-confirm__ok" disabled={busy} onClick={() => void sendPanelSms()}>ارسال</button>
            </div>
          </div>
        </div>
      ) : null}
    </>,
    document.body,
  );
}
