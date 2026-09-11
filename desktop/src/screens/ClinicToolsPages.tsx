import { useEffect, useState } from "react";
import { ErrorBanner, Field, PrimaryButton } from "../components/ui";
import { ApiError } from "../lib/api";
import {
  confirmChecklist,
  destroyChecklistItem,
  downloadBackup,
  ensureChecklist,
  loadCatalog,
  loadChecklistTemplate,
  loadSupport,
  loadSystem,
  restoreBackup,
  runBackup,
  storeChecklistItem,
  toggleChecklistItem,
  updateChecklistTemplate,
  updatePrivacy,
  updateSupport,
  type ChecklistDto,
} from "../lib/ops";
import type { BookingDto, Session } from "../lib/types";

export type { ReadyTarget } from "../components/ReadyAnswersPanel";
export { ReadyAnswersPanel, ReadyAnswersScreen, targetFromBooking } from "../components/ReadyAnswersPanel";

export function BookingExtraActions({
  item,
  onReady,
  onChecklist,
}: {
  item: BookingDto;
  onReady: () => void;
  onChecklist?: () => void;
}) {
  return (
    <div className="action-row tight">
      <button type="button" className="chip-btn" onClick={onReady}>پاسخ آماده</button>
      {item.kind === "surgery" && onChecklist ? (
        <button type="button" className="chip-btn" onClick={onChecklist}>چک‌لیست</button>
      ) : null}
    </div>
  );
}

export function ChecklistDrawer({
  session,
  surgeryId,
  onClose,
}: {
  session: Session;
  surgeryId: number;
  onClose: () => void;
}) {
  const [data, setData] = useState<ChecklistDto | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [label, setLabel] = useState("");

  useEffect(() => {
    void ensureChecklist(session, surgeryId).then(setData).catch((err) => setError(err instanceof ApiError ? err.message : "چک‌لیست نیامد."));
  }, [session, surgeryId]);

  return (
    <div className="modal-back" onClick={onClose} role="presentation">
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog">
        <div className="row-between">
          <h2>{data?.title || "چک‌لیست عمل"}</h2>
          <button type="button" className="btn-ghost btn-compact" onClick={onClose}>بستن</button>
        </div>
        <ErrorBanner message={error} />
        {data?.saved_at_jalali ? <p className="hint">ثبت در پرونده: {data.saved_at_jalali}</p> : null}
        <p className="meta">{data?.unchecked_count ?? 0} مورد باقی‌مانده</p>
        <div className="stack" style={{ marginTop: 8 }}>
          {(data?.items || []).map((item) => (
            <article key={item.id} className="card">
              <div className="row-between">
                <button type="button" className="plain-link" onClick={() => data && void toggleChecklistItem(session, data.id, item.id).then(setData).catch((err) => setError(err instanceof ApiError ? err.message : "تیک نشد."))}>
                  <strong>{item.checked ? "☑" : "☐"} {item.label}</strong>
                  {item.checked_stamp ? <p className="meta">{item.checked_stamp}</p> : null}
                </button>
                {item.is_custom ? (
                  <button type="button" className="chip-btn" onClick={() => data && void destroyChecklistItem(session, data.id, item.id).then(setData)}>حذف</button>
                ) : null}
              </div>
            </article>
          ))}
        </div>
        <div className="form-grid" style={{ marginTop: 12 }}>
          <Field label="مورد سفارشی" value={label} onChange={setLabel} />
          <PrimaryButton onClick={() => data && label.trim() && void storeChecklistItem(session, data.id, label.trim()).then((next) => { setLabel(""); setData(next); })}>افزودن</PrimaryButton>
        </div>
        {data ? <PrimaryButton onClick={() => void confirmChecklist(session, data.id).then((result) => { setError(null); if (result.checklist) setData(result.checklist); })}>ثبت در پرونده</PrimaryButton> : null}
      </div>
    </div>
  );
}

export function ChecklistSettingsPane({ session }: { session: Session }) {
  const [types, setTypes] = useState<Array<{ id: number; name?: string; subtypes?: Array<{ id: number; name?: string }> }>>([]);
  const [typeId, setTypeId] = useState(0);
  const [subtypeId, setSubtypeId] = useState<number | null>(null);
  const [lines, setLines] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void loadCatalog(session).then((data) => {
      const rows = data.surgery_types || [];
      setTypes(rows);
      if (rows[0]) setTypeId(Number(rows[0].id));
    });
  }, [session]);

  useEffect(() => {
    if (!typeId) return;
    void loadChecklistTemplate(session, typeId, subtypeId).then((data) => {
      setLines((data.items || []).map((item) => item.label).join("\n"));
    }).catch((err) => setError(err instanceof ApiError ? err.message : "قالب نیامد."));
  }, [session, typeId, subtypeId]);

  const current = types.find((item) => Number(item.id) === typeId);

  return (
    <div className="space-y-4">
      <ErrorBanner message={error} />
      <label className="field"><span>نوع عمل</span>
        <select value={typeId} onChange={(event) => { setTypeId(Number(event.target.value)); setSubtypeId(null); }}>
          {types.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </label>
      {(current?.subtypes || []).length ? (
        <label className="field"><span>زیرگروه (اختیاری)</span>
          <select value={subtypeId || ""} onChange={(event) => setSubtypeId(event.target.value ? Number(event.target.value) : null)}>
            <option value="">قالب کلی نوع عمل</option>
            {current?.subtypes?.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
          </select>
        </label>
      ) : null}
      <label className="field"><span>موارد چک‌لیست (هر خط یک مورد)</span>
        <textarea rows={10} value={lines} onChange={(event) => setLines(event.target.value)} />
      </label>
      <PrimaryButton onClick={() => void updateChecklistTemplate(session, {
        surgery_type_id: typeId,
        surgery_subtype_id: subtypeId,
        items: lines.split("\n").map((label) => ({ label: label.trim() })).filter((row) => row.label),
      }).catch((err) => setError(err instanceof ApiError ? err.message : "ذخیره نشد."))}>ذخیره قالب</PrimaryButton>
    </div>
  );
}

export function SystemScreen({ session }: { session: Session }) {
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [backups, setBackups] = useState<Array<{ name: string; size?: number; at?: number }>>([]);
  const [health, setHealth] = useState<{ app?: boolean; db?: boolean; queue_pending?: number | null; queue_failed?: number | null }>({});
  const [privacy, setPrivacy] = useState({ activity_log_days: "365", qr_cache_days: "30", retention_note: "" });
  const [isAdmin, setIsAdmin] = useState(session.user.role === "admin");
  const [confirm, setConfirm] = useState("");
  const [restoreFile, setRestoreFile] = useState("");

  async function refresh() {
    const data = await loadSystem(session);
    setBackups(data.backups || []);
    setHealth(data.health || {});
    setIsAdmin(!!data.is_admin || session.user.role === "admin");
    setPrivacy({
      activity_log_days: String(data.privacy?.activity_log_days ?? 365),
      qr_cache_days: String(data.privacy?.qr_cache_days ?? 30),
      retention_note: data.privacy?.retention_note || "",
    });
  }

  useEffect(() => { void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "وضعیت سیستم نیامد.")); }, [session]);

  return (
    <div className="page">
      <h1 className="page-title">سیستم و پشتیبان</h1>
      <ErrorBanner message={error} />
      {notice ? <p className="hint">{notice}</p> : null}
      <div className="stat-grid">
        <div className="stat"><strong>{health.db ? "سالم" : "قطع"}</strong><span>دیتابیس</span></div>
        <div className="stat"><strong>{health.queue_pending ?? "—"}</strong><span>صف در انتظار</span></div>
        <div className="stat"><strong>{health.queue_failed ?? "—"}</strong><span>صف ناموفق</span></div>
      </div>
      <div className="panel form-stack" style={{ marginTop: 16 }}>
        <PrimaryButton onClick={() => void runBackup(session).then((data) => { setNotice(data.message || "بک‌آپ گرفته شد."); return refresh(); }).catch((err) => setError(err instanceof ApiError ? err.message : "بک‌آپ نشد."))}>گرفتن بک‌آپ</PrimaryButton>
      </div>
      <div className="space-y-4">
        {backups.map((row) => (
          <article key={row.name} className="card">
            <div className="row-between">
              <div>
                <strong>{row.name}</strong>
                <p className="meta">{row.size ? `${Math.round((row.size || 0) / 1024)} کیلوبایت` : ""}</p>
              </div>
              <div className="action-row tight">
                <button type="button" className="chip-btn" onClick={() => void downloadBackup(session, row.name).catch((err) => setError(err instanceof ApiError ? err.message : "دانلود نشد."))}>دانلود</button>
                {isAdmin ? <button type="button" className="chip-btn" onClick={() => { setRestoreFile(row.name); setConfirm(""); }}>بازیابی</button> : null}
              </div>
            </div>
          </article>
        ))}
      </div>
      {restoreFile ? (
        <div className="panel form-stack" style={{ marginTop: 16 }}>
          <p className="lede">برای بازیابی «{restoreFile}» کلمه RESTORE را بنویسید.</p>
          <Field label="تأیید" value={confirm} onChange={setConfirm} />
          <PrimaryButton onClick={() => {
            if (confirm !== "RESTORE") { setError("کلمه تأیید نادرست است."); return; }
            void restoreBackup(session, restoreFile).then((data) => { setNotice(data.message || "بازیابی شد."); setRestoreFile(""); }).catch((err) => setError(err instanceof ApiError ? err.message : "بازیابی نشد."));
          }}>بازیابی دیتابیس</PrimaryButton>
        </div>
      ) : null}
      <div className="panel form-stack" style={{ marginTop: 16 }}>
        <h3>سیاست نگه‌داشت</h3>
        <div className="form-grid">
          <Field label="روز نگهداری لاگ" value={privacy.activity_log_days} onChange={(value) => setPrivacy({ ...privacy, activity_log_days: value })} />
          <Field label="روز کش QR" value={privacy.qr_cache_days} onChange={(value) => setPrivacy({ ...privacy, qr_cache_days: value })} />
        </div>
        <label className="field"><span>یادداشت</span><textarea value={privacy.retention_note} onChange={(event) => setPrivacy({ ...privacy, retention_note: event.target.value })} /></label>
        <PrimaryButton onClick={() => void updatePrivacy(session, { activity_log_days: Number(privacy.activity_log_days) || 365, qr_cache_days: Number(privacy.qr_cache_days) || 30, retention_note: privacy.retention_note }).then(() => setNotice("سیاست ذخیره شد.")).catch((err) => setError(err instanceof ApiError ? err.message : "ذخیره نشد."))}>ذخیره سیاست</PrimaryButton>
      </div>
    </div>
  );
}

export function SupportScreen({ session }: { session: Session }) {
  const [telegram, setTelegram] = useState("");
  const [phone, setPhone] = useState("");
  const [email, setEmail] = useState("");
  const [sla, setSla] = useState("24");
  const [notes, setNotes] = useState("");
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    void loadSupport(session).then((data) => {
      setTelegram(data.telegram || "");
      setPhone(data.phone || "");
      setEmail(data.email || "");
      setSla(String(data.sla_hours ?? 24));
      setNotes(data.notes || "");
    }).catch((err) => setError(err instanceof ApiError ? err.message : "پشتیبانی نیامد."));
  }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">پشتیبانی</h1>
      <ErrorBanner message={error} />
      <div className="panel form-stack">
        <Field label="تلگرام" value={telegram} onChange={setTelegram} />
        <Field label="تلفن" value={phone} onChange={setPhone} />
        <Field label="ایمیل" value={email} onChange={setEmail} />
        <Field label="ساعت SLA" value={sla} onChange={setSla} />
        <label className="field"><span>یادداشت</span><textarea value={notes} onChange={(event) => setNotes(event.target.value)} /></label>
        <PrimaryButton onClick={() => void updateSupport(session, { telegram, phone, email, sla_hours: Number(sla) || 24, notes }).catch((err) => setError(err instanceof ApiError ? err.message : "ذخیره نشد."))}>ذخیره پشتیبانی</PrimaryButton>
      </div>
    </div>
  );
}
