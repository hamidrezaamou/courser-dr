import { FormEvent, useEffect, useState } from "react";
import { ErrorBanner, Field, LoadingBox, PrimaryButton } from "../components/ui";
import { ApiError } from "../lib/api";
import {
  approveBooking,
  convertWaiting,
  destroyUser,
  followupAction,
  loadAccounting,
  loadApprovals,
  loadCatalog,
  loadMessages,
  loadUsers,
  loadWaiting,
  markMessagesRead,
  rejectBooking,
  storeAccounting,
  storeFollowup,
  storeUser,
  storeWaiting,
  type FeatureFlagDto,
  type LedgerRow,
  type MessageDto,
  type StaffUserDto,
  type WaitingDto,
  updateFeatures,
  updateQuickLinks,
  waitingStatus,
} from "../lib/ops";
import { joinMeta } from "../lib/status";
import type { BookingDto, BookingPrefill, Session } from "../lib/types";

export function MessagesScreen({
  session,
  onOpen,
}: {
  session: Session;
  onOpen: (id: number) => void;
}) {
  const [filter, setFilter] = useState("unread");
  const [items, setItems] = useState<MessageDto[]>([]);
  const [unread, setUnread] = useState(0);
  const [error, setError] = useState<string | null>(null);

  async function refresh() {
    const data = await loadMessages(session, filter);
    setItems(data.items || []);
    setUnread(data.unread || 0);
  }

  useEffect(() => {
    void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "پیام‌ها بارگذاری نشد."));
  }, [session, filter]);

  return (
    <div className="page">
      <header className="page-head">
        <div>
          <h1 className="page-title">پیام‌های پرونده</h1>
          <p className="lede">{unread} خوانده‌نشده</p>
        </div>
        <button type="button" className="btn-secondary btn-compact" onClick={() => void markMessagesRead(session).then(refresh)}>
          همه خوانده شد
        </button>
      </header>
      <div className="chip-row">
        <button type="button" className={filter === "unread" ? "board-chip is-active" : "board-chip"} onClick={() => setFilter("unread")}>خوانده‌نشده</button>
        <button type="button" className={filter === "all" ? "board-chip is-active" : "board-chip"} onClick={() => setFilter("all")}>همه</button>
      </div>
      <ErrorBanner message={error} />
      <div className="stack" style={{ marginTop: 12 }}>
        {items.map((item) => (
          <button key={item.id} type="button" className="card card-btn" onClick={() => item.patient_id && onOpen(item.patient_id)}>
            <strong>{item.actor_name} برای {item.patient_name}</strong>
            <p className="meta">{item.created_at_jalali} {item.read ? "· خوانده‌شده" : ""}</p>
          </button>
        ))}
      </div>
      {items.length === 0 ? <p className="hint">پیامی در این فهرست نیست.</p> : null}
    </div>
  );
}

export function WaitingScreen({
  session,
  onBookVisit,
  onBookSurgery,
}: {
  session: Session;
  onBookVisit: (opts?: { patientId?: number; bookingId?: number }) => void;
  onBookSurgery: (opts?: { patientId?: number; prefill?: BookingPrefill }) => void;
}) {
  const [entries, setEntries] = useState<WaitingDto[]>([]);
  const [name, setName] = useState("");
  const [mobile, setMobile] = useState("");
  const [kind, setKind] = useState("visit");
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

  async function refresh() {
    setEntries((await loadWaiting(session)).entries || []);
  }
  useEffect(() => {
    void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "لیست انتظار بارگذاری نشد."));
  }, [session]);

  async function convert(item: WaitingDto) {
    setBusyId(item.id);
    setError(null);
    try {
      const result = await convertWaiting(session, item.id);
      if (result.kind === "visit" && result.appointment_id) {
        onBookVisit({ patientId: result.patient_id || undefined, bookingId: result.appointment_id });
        return;
      }
      onBookSurgery({
        patientId: result.patient_id || result.prefill?.patient_id || undefined,
        prefill: result.prefill || {
          patient_id: item.patient_id,
          name: item.patient_name,
          mobile: item.mobile,
          national_code: item.national_code,
          date: item.preferred_jalali,
          notes: item.notes,
          waiting_id: item.id,
        },
      });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "تبدیل به نوبت انجام نشد.");
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="page">
      <h1 className="page-title">لیست انتظار</h1>
      <ErrorBanner message={error} />
      <form
        className="panel form-stack"
        onSubmit={(event: FormEvent) => {
          event.preventDefault();
          void storeWaiting(session, { patient_name: name, mobile, kind })
            .then(() => { setName(""); setMobile(""); return refresh(); })
            .catch((err) => setError(err instanceof ApiError ? err.message : "ثبت نشد."));
        }}
      >
        <div className="form-grid">
          <Field label="نام" value={name} onChange={setName} />
          <Field label="موبایل" value={mobile} onChange={setMobile} />
        </div>
        <div className="chip-row">
          <button type="button" className={kind === "visit" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("visit")}>ویزیت</button>
          <button type="button" className={kind === "surgery" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("surgery")}>عمل</button>
        </div>
        <PrimaryButton type="submit">افزودن به صف انتظار</PrimaryButton>
      </form>
      <div className="stack" style={{ marginTop: 16 }}>
        {entries.map((item) => (
          <article key={item.id} className="card">
            <strong>{item.patient_name}</strong>
            <p className="meta">{joinMeta([item.mobile, item.kind === "surgery" ? "عمل" : "ویزیت", item.status, item.preferred_jalali])}</p>
            <div className="action-row tight">
              <button type="button" className="chip-btn" onClick={() => void waitingStatus(session, item.id, "contacted").then(refresh)}>تماس شد</button>
              <button type="button" className="chip-btn" disabled={busyId === item.id} onClick={() => void convert(item)}>
                {busyId === item.id ? "در حال تبدیل…" : "ساخت نوبت"}
              </button>
              <button type="button" className="chip-btn" onClick={() => void waitingStatus(session, item.id, "cancelled").then(refresh)}>لغو</button>
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}

export function AccountingScreen({ session }: { session: Session }) {
  const [date, setDate] = useState("");
  const [rows, setRows] = useState<LedgerRow[]>([]);
  const [income, setIncome] = useState(0);
  const [charges, setCharges] = useState(0);
  const [patientId, setPatientId] = useState("");
  const [amount, setAmount] = useState("");
  const [label, setLabel] = useState("دریافت");
  const [type, setType] = useState("payment");
  const [error, setError] = useState<string | null>(null);

  async function refresh(next = date) {
    const data = await loadAccounting(session, next);
    setDate(data.date || next);
    setRows(data.rows || []);
    setIncome(data.income || 0);
    setCharges(data.charges || 0);
  }
  useEffect(() => {
    void refresh("").catch((err) => setError(err instanceof ApiError ? err.message : "دفتر بارگذاری نشد."));
  }, [session]);

  return (
    <div className="page">
      <header className="page-head">
        <h1 className="page-title">حسابداری</h1>
        <div className="stat-grid" style={{ maxWidth: 320 }}>
          <div className="stat"><strong>{income.toLocaleString("fa-IR")}</strong><span>دریافت امروز</span></div>
          <div className="stat"><strong>{charges.toLocaleString("fa-IR")}</strong><span>بدهکاری امروز</span></div>
        </div>
      </header>
      <ErrorBanner message={error} />
      <Field label="تاریخ دفتر" value={date} onChange={(value) => { setDate(value); void refresh(value); }} />
      <form
        className="panel form-stack"
        onSubmit={(event: FormEvent) => {
          event.preventDefault();
          void storeAccounting(session, {
            patient_id: Number(patientId),
            type,
            amount: Number(amount),
            label,
            transaction_date: date,
            method: "cash",
          }).then(() => refresh()).catch((err) => setError(err instanceof ApiError ? err.message : "تراکنش ثبت نشد."));
        }}
      >
        <div className="form-grid">
          <Field label="شناسه بیمار" value={patientId} onChange={setPatientId} />
          <Field label="مبلغ" value={amount} onChange={setAmount} />
        </div>
        <Field label="عنوان" value={label} onChange={setLabel} />
        <div className="chip-row">
          <button type="button" className={type === "payment" ? "board-chip is-active" : "board-chip"} onClick={() => setType("payment")}>دریافت</button>
          <button type="button" className={type === "charge" ? "board-chip is-active" : "board-chip"} onClick={() => setType("charge")}>بدهکاری</button>
        </div>
        <PrimaryButton type="submit">ثبت تراکنش</PrimaryButton>
      </form>
      <div className="stack" style={{ marginTop: 16 }}>
        {rows.map((row) => (
          <article key={row.id} className="card">
            <strong>{row.patient_name} · {(row.amount || 0).toLocaleString("fa-IR")}</strong>
            <p className="meta">{joinMeta([row.type === "payment" ? "دریافت" : "بدهکاری", row.label, row.method])}</p>
          </article>
        ))}
      </div>
    </div>
  );
}

export function ApprovalScreen({ session, onOpen }: { session: Session; onOpen: (id: number) => void }) {
  const [rows, setRows] = useState<BookingDto[]>([]);
  const [error, setError] = useState<string | null>(null);
  async function refresh() {
    setRows((await loadApprovals(session)).pending || []);
  }
  useEffect(() => {
    void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "صف تأیید بارگذاری نشد."));
  }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">تأیید نوبت آنلاین</h1>
      <ErrorBanner message={error} />
      <div className="stack">
        {rows.map((row) => (
          <article key={row.id} className="card">
            <button type="button" className="plain-link" onClick={() => row.patient_id && onOpen(row.patient_id)}>
              <strong>{row.patient_name}</strong>
              <p className="meta">{joinMeta([row.scheduled_date_jalali, row.scheduled_time_label, row.title])}</p>
            </button>
            <div className="action-row tight">
              <button type="button" className="btn-primary btn-compact" onClick={() => void approveBooking(session, row.id).then(refresh)}>تأیید</button>
              <button type="button" className="btn-ghost btn-compact" onClick={() => void rejectBooking(session, row.id).then(refresh)}>رد</button>
            </div>
          </article>
        ))}
      </div>
      {rows.length === 0 ? <p className="hint">نوبت معلقی نیست.</p> : null}
    </div>
  );
}

export function AdminUsersScreen({ session }: { session: Session }) {
  const [users, setUsers] = useState<StaffUserDto[]>([]);
  const [name, setName] = useState("");
  const [national, setNational] = useState("");
  const [role, setRole] = useState("assistant");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  async function refresh() {
    setUsers((await loadUsers(session)).users || []);
  }
  useEffect(() => {
    void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "کاربران بارگذاری نشد."));
  }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">کاربران و نقش‌ها</h1>
      <ErrorBanner message={error} />
      <form className="panel form-stack" onSubmit={(event: FormEvent) => {
        event.preventDefault();
        void storeUser(session, { name, national_code: national, role, password })
          .then(() => { setName(""); setNational(""); setPassword(""); return refresh(); })
          .catch((err) => setError(err instanceof ApiError ? err.message : "کاربر ساخته نشد."));
      }}>
        <div className="form-grid">
          <Field label="نام" value={name} onChange={setName} />
          <Field label="کد ملی" value={national} onChange={setNational} />
          <Field label="رمز" value={password} onChange={setPassword} type="password" />
          <label className="field"><span>نقش</span>
            <select value={role} onChange={(event) => setRole(event.target.value)}>
              <option value="admin">مدیر</option>
              <option value="doctor">پزشک</option>
              <option value="assistant">منشی</option>
            </select>
          </label>
        </div>
        <PrimaryButton type="submit">ثبت کاربر</PrimaryButton>
      </form>
      <div className="stack" style={{ marginTop: 16 }}>
        {users.map((user) => (
          <article key={user.id} className="card">
            <div className="row-between">
              <div>
                <strong>{user.name}</strong>
                <p className="meta">{joinMeta([user.role, user.national_code, user.mobile])}</p>
              </div>
              {user.id !== session.user.id ? (
                <button type="button" className="chip-btn" onClick={() => void destroyUser(session, user.id).then(refresh)}>حذف</button>
              ) : null}
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}

export function FeaturesScreen({ session }: { session: Session }) {
  const [flags, setFlags] = useState<FeatureFlagDto[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  useEffect(() => {
    void loadCatalog(session).then((data) => setFlags(data.features || [])).catch((err) => setError(err instanceof ApiError ? err.message : "قابلیت‌ها نیامد."));
  }, [session]);
  return (
    <div className="page">
      <header className="page-head">
        <h1 className="page-title">قابلیت‌ها</h1>
        <PrimaryButton onClick={() => {
          const map: Record<string, boolean> = {};
          flags.forEach((item) => { map[item.key] = item.enabled; });
          void updateFeatures(session, map).then(() => setSaved(true)).catch((err) => setError(err instanceof ApiError ? err.message : "ذخیره نشد."));
        }}>ذخیره</PrimaryButton>
      </header>
      <ErrorBanner message={error} />
      {saved ? <p className="hint">ذخیره شد.</p> : null}
      <div className="stack">
        {flags.map((item) => (
          <label key={item.key} className="card" style={{ display: "flex", gap: 12, alignItems: "center" }}>
            <input type="checkbox" checked={item.enabled} onChange={(event) => setFlags((current) => current.map((row) => (row.key === item.key ? { ...row, enabled: event.target.checked } : row)))} />
            <div>
              <strong>{item.label}</strong>
              <p className="hint">{item.hint}</p>
            </div>
          </label>
        ))}
      </div>
    </div>
  );
}

export function QuickLinksScreen({ session }: { session: Session }) {
  const [links, setLinks] = useState<Array<{ title: string; url: string }>>([{ title: "", url: "" }]);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    void loadCatalog(session).then((data) => setLinks(data.quick_links?.length ? data.quick_links : [{ title: "", url: "" }]));
  }, [session]);
  return (
    <div className="page">
      <header className="page-head">
        <h1 className="page-title">لینک‌های ویژه هدر</h1>
        <PrimaryButton onClick={() => void updateQuickLinks(session, links.filter((item) => item.title && item.url)).catch((err) => setError(err instanceof ApiError ? err.message : "ذخیره نشد."))}>ذخیره</PrimaryButton>
      </header>
      <ErrorBanner message={error} />
      <div className="stack">
        {links.map((link, index) => (
          <div key={index} className="form-grid">
            <Field label="عنوان" value={link.title} onChange={(value) => setLinks((current) => current.map((row, i) => (i === index ? { ...row, title: value } : row)))} />
            <Field label="آدرس" value={link.url} onChange={(value) => setLinks((current) => current.map((row, i) => (i === index ? { ...row, url: value } : row)))} />
          </div>
        ))}
      </div>
      <button type="button" className="btn-ghost" style={{ marginTop: 12 }} onClick={() => setLinks((current) => [...current, { title: "", url: "" }])}>افزودن لینک</button>
    </div>
  );
}

export function FollowupComposer({
  session,
  patientId,
  onDone,
}: {
  session: Session;
  patientId: number;
  onDone: () => void;
}) {
  const [title, setTitle] = useState("پیگیری دستی");
  const [due, setDue] = useState("");
  const [error, setError] = useState<string | null>(null);
  return (
    <form className="form-stack" onSubmit={(event: FormEvent) => {
      event.preventDefault();
      void storeFollowup(session, { patient_id: patientId, title, due_date: due })
        .then(onDone)
        .catch((err) => setError(err instanceof ApiError ? err.message : "پیگیری ثبت نشد."));
    }}>
      <ErrorBanner message={error} />
      <Field label="عنوان" value={title} onChange={setTitle} />
      <Field label="تاریخ سررسید (جلالی)" value={due} onChange={setDue} />
      <PrimaryButton type="submit">ثبت پیگیری</PrimaryButton>
    </form>
  );
}

export function FollowupActions({ session, id, onDone }: { session: Session; id: number; onDone: () => void }) {
  return (
    <div className="action-row tight">
      <button type="button" className="chip-btn" onClick={() => void followupAction(session, id, "start").then(onDone)}>شروع</button>
      <button type="button" className="chip-btn" onClick={() => void followupAction(session, id, "complete", { outcome: "done" }).then(onDone)}>انجام شد</button>
      <button type="button" className="chip-btn" onClick={() => void followupAction(session, id, "fail", { status: "failed" }).then(onDone)}>ناموفق</button>
      <button type="button" className="chip-btn" onClick={() => void followupAction(session, id, "retry", { days_after: 7 }).then(onDone)}>پیگیری مجدد</button>
      <button type="button" className="chip-btn" onClick={() => void followupAction(session, id, "cancel").then(onDone)}>لغو</button>
    </div>
  );
}

export { LoadingBox };
