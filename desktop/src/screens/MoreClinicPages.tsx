import { FormEvent, useEffect, useState } from "react";
import { ErrorBanner, Field, PrimaryButton } from "../components/ui";
import { ApiError } from "../lib/api";
import {
  loadActivityLogs,
  loadBilling,
  loadBrand,
  loadCommunications,
  loadConsent,
  loadContacts,
  downloadContactsVcf,
  loadEyeChart,
  loadHis,
  loadManagePatients,
  loadPortal,
  loadProgramGroups,
  loadQuality,
  loadRxPrint,
  loadRxPrints,
  openPrintHtml,
  secureErase,
  storeBilling,
  storeConsent,
  storeConsentTemplate,
  storeProgramGroup,
  storeTariff,
  updateBrand,
  updateCommunications,
} from "../lib/ops";
import { joinMeta } from "../lib/status";
import type { Session } from "../lib/types";

function useError() {
  const [error, setError] = useState<string | null>(null);
  const catchErr = (err: unknown, fallback: string) => setError(err instanceof ApiError ? err.message : fallback);
  return { error, setError, catchErr };
}

export function BillingScreen({ session }: { session: Session }) {
  const { error, catchErr } = useError();
  const [tariffs, setTariffs] = useState<Array<{ id: number; name: string; kind: string; amount: number; insurance_coverage: number }>>([]);
  const [records, setRecords] = useState<Array<{ id: number; patient_name?: string; fee_amount?: number; settlement_status?: string; tariff?: string }>>([]);
  const [visits, setVisits] = useState<Array<{ id: number; patient_name?: string }>>([]);
  const [name, setName] = useState("");
  const [amount, setAmount] = useState("0");
  const [kind, setKind] = useState("visit");
  const [coverage, setCoverage] = useState("0");
  const [tariffId, setTariffId] = useState("");
  const [billableId, setBillableId] = useState("");

  async function refresh() {
    const data = await loadBilling(session);
    setTariffs(data.tariffs || []);
    setRecords(data.records || []);
    setVisits(data.recent_visits || []);
  }

  useEffect(() => {
    void refresh().catch((err) => catchErr(err, "صورتحساب بارگذاری نشد."));
  }, [session]);

  return (
    <div className="page">
      <h1 className="page-title">صورتحساب و بیمه</h1>
      <ErrorBanner message={error} />
      <form className="panel form-stack" onSubmit={(event: FormEvent) => {
        event.preventDefault();
        void storeTariff(session, { name, kind, amount: Number(amount) || 0, insurance_coverage: Number(coverage) || 0 })
          .then(() => { setName(""); return refresh(); })
          .catch((err) => catchErr(err, "تعرفه ذخیره نشد."));
      }}>
        <div className="form-grid">
          <Field label="نام تعرفه" value={name} onChange={setName} />
          <Field label="مبلغ" value={amount} onChange={setAmount} />
          <Field label="پوشش بیمه ٪" value={coverage} onChange={setCoverage} />
        </div>
        <div className="chip-row">
          <button type="button" className={kind === "visit" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("visit")}>ویزیت</button>
          <button type="button" className={kind === "surgery" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("surgery")}>عمل</button>
        </div>
        <PrimaryButton type="submit">ثبت تعرفه</PrimaryButton>
      </form>
      <form className="panel form-stack" style={{ marginTop: 16 }} onSubmit={(event: FormEvent) => {
        event.preventDefault();
        void storeBilling(session, { billable_type: "visit", billable_id: Number(billableId), service_tariff_id: Number(tariffId), settlement_status: "open" })
          .then(refresh)
          .catch((err) => catchErr(err, "صورتحساب ثبت نشد."));
      }}>
        <label className="field"><span>نوبت ویزیت</span>
          <select value={billableId} onChange={(event) => setBillableId(event.target.value)}>
            <option value="">انتخاب نوبت</option>
            {visits.map((item) => <option key={item.id} value={item.id}>{item.patient_name || item.id}</option>)}
          </select>
        </label>
        <label className="field"><span>تعرفه</span>
          <select value={tariffId} onChange={(event) => setTariffId(event.target.value)}>
            <option value="">انتخاب تعرفه</option>
            {tariffs.map((item) => <option key={item.id} value={item.id}>{item.name} · {item.amount}</option>)}
          </select>
        </label>
        <PrimaryButton type="submit">ثبت صورتحساب</PrimaryButton>
      </form>
      <div className="stack" style={{ marginTop: 16 }}>
        {records.map((row) => (
          <article key={row.id} className="card">
            <strong>{row.patient_name || "—"}</strong>
            <p className="meta">{joinMeta([row.tariff, String(row.fee_amount ?? ""), row.settlement_status])}</p>
          </article>
        ))}
      </div>
    </div>
  );
}

export function ConsentScreen({ session }: { session: Session }) {
  const { error, catchErr } = useError();
  const [templates, setTemplates] = useState<Array<{ id: number; title: string; kind: string }>>([]);
  const [recent, setRecent] = useState<Array<{ id: number; patient_name?: string; template?: string; signed_by_name?: string }>>([]);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [kind, setKind] = useState("visit");
  const [patientId, setPatientId] = useState("");
  const [templateId, setTemplateId] = useState("");
  const [signedBy, setSignedBy] = useState("");

  async function refresh() {
    const data = await loadConsent(session);
    setTemplates(data.templates || []);
    setRecent(data.recent || []);
  }
  useEffect(() => { void refresh().catch((err) => catchErr(err, "رضایت‌نامه‌ها نیامد.")); }, [session]);

  return (
    <div className="page">
      <h1 className="page-title">رضایت‌نامه</h1>
      <ErrorBanner message={error} />
      <form className="panel form-stack" onSubmit={(event: FormEvent) => {
        event.preventDefault();
        void storeConsentTemplate(session, { title, kind, body }).then(() => { setTitle(""); setBody(""); return refresh(); }).catch((err) => catchErr(err, "قالب ذخیره نشد."));
      }}>
        <Field label="عنوان قالب" value={title} onChange={setTitle} />
        <label className="field"><span>متن</span><textarea value={body} onChange={(event) => setBody(event.target.value)} /></label>
        <div className="chip-row">
          <button type="button" className={kind === "visit" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("visit")}>ویزیت</button>
          <button type="button" className={kind === "surgery" ? "board-chip is-active" : "board-chip"} onClick={() => setKind("surgery")}>عمل</button>
        </div>
        <PrimaryButton type="submit">ثبت قالب</PrimaryButton>
      </form>
      <form className="panel form-stack" style={{ marginTop: 16 }} onSubmit={(event: FormEvent) => {
        event.preventDefault();
        void storeConsent(session, { patient_id: Number(patientId), consent_template_id: Number(templateId), signed_by_name: signedBy })
          .then(() => { setPatientId(""); setSignedBy(""); return refresh(); })
          .catch((err) => catchErr(err, "رضایت‌نامه ثبت نشد."));
      }}>
        <Field label="شناسه بیمار" value={patientId} onChange={setPatientId} />
        <label className="field"><span>قالب</span>
          <select value={templateId} onChange={(event) => setTemplateId(event.target.value)}>
            <option value="">انتخاب قالب</option>
            {templates.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}
          </select>
        </label>
        <Field label="امضاکننده" value={signedBy} onChange={setSignedBy} />
        <PrimaryButton type="submit">ثبت رضایت‌نامه</PrimaryButton>
      </form>
      <div className="stack" style={{ marginTop: 16 }}>
        {recent.map((row) => (
          <article key={row.id} className="card">
            <strong>{row.patient_name}</strong>
            <p className="meta">{joinMeta([row.template, row.signed_by_name])}</p>
          </article>
        ))}
      </div>
    </div>
  );
}

export function PortalScreen({ session }: { session: Session }) {
  const [portal, setPortal] = useState(0);
  const [total, setTotal] = useState(0);
  useEffect(() => { void loadPortal(session).then((data) => { setPortal(data.portal_patients || 0); setTotal(data.total_patients || 0); }); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">پرتال بیمار</h1>
      <div className="stat-grid">
        <div className="stat"><strong>{portal}</strong><span>بیمار با حساب پرتال</span></div>
        <div className="stat"><strong>{total}</strong><span>کل بیماران</span></div>
      </div>
      <p className="hint">ورود پرتال با کد ملی و رمز حساب بیمار روی سایت انجام می‌شود.</p>
    </div>
  );
}

export function QualityScreen({ session }: { session: Session }) {
  const [month, setMonth] = useState("");
  const [stats, setStats] = useState<Record<string, number>>({});
  useEffect(() => { void loadQuality(session).then((data) => { setMonth(data.month || ""); setStats(data.stats || {}); }); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">داشبورد کیفیت</h1>
      <p className="lede">{month}</p>
      <div className="stat-grid">
        <div className="stat"><strong>{stats.total ?? 0}</strong><span>کل نوبت ماه</span></div>
        <div className="stat"><strong>{stats.done ?? 0}</strong><span>انجام‌شده</span></div>
        <div className="stat"><strong>{stats.no_show ?? 0}</strong><span>عدم مراجعه</span></div>
        <div className="stat"><strong>{stats.cancelled ?? 0}</strong><span>لغو</span></div>
        <div className="stat"><strong>{stats.done_rate ?? 0}٪</strong><span>نرخ انجام</span></div>
        <div className="stat"><strong>{stats.no_show_rate ?? 0}٪</strong><span>نرخ عدم مراجعه</span></div>
      </div>
    </div>
  );
}

export function EyeChartScreen({ session, onOpen }: { session: Session; onOpen: (id: number) => void }) {
  const [rows, setRows] = useState<Array<{ id: number; patient_id?: number; patient_name?: string; va_right?: string; va_left?: string; iop_right?: string; iop_left?: string; created_at_jalali?: string }>>([]);
  useEffect(() => { void loadEyeChart(session).then((data) => setRows(data.visits || [])); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">جدول بینایی</h1>
      <div className="stack">
        {rows.map((row) => (
          <button key={row.id} type="button" className="card card-btn" onClick={() => row.patient_id && onOpen(row.patient_id)}>
            <strong>{row.patient_name}</strong>
            <p className="meta">{joinMeta([row.created_at_jalali, row.va_right ? `VA R ${row.va_right}` : "", row.va_left ? `VA L ${row.va_left}` : "", row.iop_right ? `IOP R ${row.iop_right}` : "", row.iop_left ? `IOP L ${row.iop_left}` : ""])}</p>
          </button>
        ))}
      </div>
      {rows.length === 0 ? <p className="hint">معاینه با VA ثبت نشده است.</p> : null}
    </div>
  );
}

export function RxPrintScreen({ session }: { session: Session }) {
  const [rows, setRows] = useState<Array<{ id: number; patient_name?: string; created_at_jalali?: string; items?: Array<{ drug_name?: string }> }>>([]);
  useEffect(() => { void loadRxPrints(session).then((data) => setRows(data.prescriptions || [])); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">نسخه چاپی</h1>
      <div className="stack">
        {rows.map((row) => (
          <article key={row.id} className="card">
            <div className="row-between">
              <div>
                <strong>{row.patient_name}</strong>
                <p className="meta">{joinMeta([row.created_at_jalali, (row.items || []).map((item) => item.drug_name).join("، ")])}</p>
              </div>
              <button type="button" className="chip-btn" onClick={() => void loadRxPrint(session, row.id).then((data) => data.html && openPrintHtml(data.html))}>چاپ</button>
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}

export function ProgramsPane({ session }: { session: Session }) {
  const [name, setName] = useState("");
  const [groups, setGroups] = useState<Array<{ id: number; name: string; is_active?: boolean }>>([]);
  async function refresh() { setGroups((await loadProgramGroups(session)).groups || []); }
  useEffect(() => { void refresh(); }, [session]);
  return (
    <div className="space-y-5">
      <p className="text-sm leading-7" style={{ color: "var(--muted)" }}>
        هر گروه برنامه ظرفیت مستقل خودش را دارد. اگر گروه A در یک بیمارستان پر شود، ظرفیت گروه B عوض نمی‌شود.
        یک نوع عمل یا زیرگروه فقط در یک گروه می‌تواند باشد.
      </p>
      <div className="grid gap-5 lg:grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        <section className="panel p-5">
          <h3 className="mb-4 text-sm font-bold" style={{ color: "var(--ink)" }}>افزودن گروه برنامه</h3>
          <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            void storeProgramGroup(session, name).then(() => { setName(""); return refresh(); });
          }}>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نام گروه</label>
              <input className="field-input mt-1 block w-full" value={name} onChange={(event) => setName(event.target.value)} required placeholder="مثال: گروه A" />
            </div>
            <button type="submit" className="btn-primary w-full !py-3">ذخیره گروه</button>
          </form>
        </section>
        <section className="panel overflow-hidden p-0">
          <div className="flex items-center justify-between border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>گروه‌های ثبت‌شده</h3>
            <span className="rounded-full px-3 py-1 text-xs font-bold" style={{ background: "var(--brand-soft)", color: "var(--brand-dark)" }}>{groups.length} گروه</span>
          </div>
          {groups.length === 0 ? <div className="pp-empty">هنوز گروه برنامه‌ای نیست.</div> : (
            <div className="divide-y" style={{ borderColor: "var(--line)" }}>
              {groups.map((item) => (
                <div key={item.id} className="px-5 py-3">
                  <strong style={{ color: "var(--ink)" }}>{item.name}</strong>
                  <span className="mr-2 text-xs" style={{ color: "var(--muted)" }}>{item.is_active === false ? "غیرفعال" : "فعال"}</span>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}

export function ContactsPane({ session }: { session: Session }) {
  const [rows, setRows] = useState<Array<{ name?: string; phones?: string[] }>>([]);
  const [notice, setNotice] = useState<string | null>(null);
  useEffect(() => { void loadContacts(session).then((data) => setRows(data.contacts || [])); }, [session]);
  return (
    <div className="space-y-5">
      <div className="panel p-5 sm:p-6 space-y-4">
        <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>انتقال شماره بیماران به گوشی</h3>
        <p className="text-sm leading-7" style={{ color: "var(--muted)" }}>
          نام و شمارهٔ همهٔ بیماران فعال به مخاطبین گوشی اضافه می‌شود.
          اگر شماره‌ای از قبل روی گوشی باشد دست نمی‌خورد؛ فقط شماره‌های جدید اضافه می‌شوند.
        </p>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="btn-primary"
            onClick={() => void downloadContactsVcf(session).then(() => setNotice("فایل vCard دانلود شد.")).catch((err) => setNotice(err instanceof Error ? err.message : "دانلود نشد."))}
          >
            دانلود فایل مخاطب
          </button>
        </div>
        <p className="text-xs leading-6" style={{ color: "var(--muted)" }}>از مرورگر یا برنامه دسکتاپ می‌توانید فایل را دانلود و وارد دفترچه تلفن کنید.</p>
        {notice ? <p className="text-xs font-bold" style={{ color: "var(--brand-dark)" }}>{notice}</p> : null}
      </div>
      {rows.length ? (
        <section className="panel overflow-hidden p-0">
          <div className="border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>پیش‌نمایش مخاطبین</h3>
          </div>
          <div className="divide-y" style={{ borderColor: "var(--line)" }}>
            {rows.slice(0, 40).map((row, index) => (
              <div key={`${row.name}-${index}`} className="px-5 py-3">
                <strong style={{ color: "var(--ink)" }}>{row.name}</strong>
                <p className="text-xs" dir="ltr" style={{ color: "var(--muted)" }}>{(row.phones || []).join(" · ")}</p>
              </div>
            ))}
          </div>
        </section>
      ) : null}
    </div>
  );
}

export function PatientsManagePane({ session, onOpen }: { session: Session; onOpen?: (id: number) => void }) {
  const [q, setQ] = useState("");
  const [rows, setRows] = useState<Array<{ id: number; name?: string; national_code?: string; mobile?: string }>>([]);
  const [confirm, setConfirm] = useState<{ id: number; name: string } | null>(null);
  const [typed, setTyped] = useState("");
  async function refresh() { setRows((await loadManagePatients(session, q)).patients || []); }
  useEffect(() => { void refresh(); }, [session]);
  return (
    <div className="space-y-4">
      <form className="admin-filter panel" onSubmit={(event) => { event.preventDefault(); void refresh(); }}>
        <div className="admin-filter__field flex-1">
          <label className="admin-filter__label">جستجو</label>
          <input type="search" className="field-input" placeholder="نام، کد ملی یا موبایل..." value={q} onChange={(event) => setQ(event.target.value)} />
        </div>
        <button type="submit" className="btn-secondary !py-2 !px-4 !text-sm">جستجو</button>
      </form>
      <section className="panel overflow-hidden p-0">
        {rows.length === 0 ? <div className="pp-empty">بیماری پیدا نشد.</div> : (
          <div className="divide-y" style={{ borderColor: "var(--line)" }}>
            {rows.map((row) => (
              <div key={row.id} className="flex items-center justify-between gap-3 px-5 py-3">
                <button type="button" className="min-w-0 text-right" onClick={() => onOpen?.(row.id)}>
                  <strong className="block" style={{ color: "var(--ink)" }}>{row.name}</strong>
                  <p className="text-xs" dir="ltr" style={{ color: "var(--muted)" }}>{joinMeta([row.national_code, row.mobile])}</p>
                </button>
                <button type="button" className="text-xs font-bold text-red-600" onClick={() => setConfirm({ id: row.id, name: row.name || "" })}>حذف امن</button>
              </div>
            ))}
          </div>
        )}
      </section>
      {confirm ? (
        <div className="modal-back" onClick={() => setConfirm(null)} role="presentation">
          <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog">
            <h2>حذف امن پرونده</h2>
            <p className="lede">برای تأیید، نام «{confirm.name}» را عیناً بنویسید.</p>
            <Field label="نام بیمار" value={typed} onChange={setTyped} />
            <div className="action-row">
              <button type="button" className="btn-primary" onClick={() => void secureErase(session, confirm.id, typed).then(() => { setConfirm(null); setTyped(""); return refresh(); })}>پاکسازی</button>
              <button type="button" className="btn-ghost" onClick={() => setConfirm(null)}>انصراف</button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}

export function CommunicationsScreen({ session }: { session: Session }) {
  const { error, catchErr } = useError();
  const [reminders, setReminders] = useState(true);
  const [sms, setSms] = useState(false);
  const [days, setDays] = useState("1");
  const [time, setTime] = useState("09:00");
  const [visitSms, setVisitSms] = useState(false);
  useEffect(() => {
    void loadCommunications(session).then((data) => {
      setReminders(!!data.reminders_enabled);
      setSms(!!data.sms_enabled);
      setDays(String(data.days_ahead ?? 1));
      setTime(data.send_time || "09:00");
      setVisitSms(!!data.visit_sms_on_booking);
    }).catch((err) => catchErr(err, "تنظیمات پیام نیامد."));
  }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">ارتباطات و پیامک</h1>
      <ErrorBanner message={error} />
      <div className="panel form-stack">
        <div className="chip-row">
          <button type="button" className={reminders ? "board-chip is-active" : "board-chip"} onClick={() => setReminders((v) => !v)}>یادآوری نوبت</button>
          <button type="button" className={sms ? "board-chip is-active" : "board-chip"} onClick={() => setSms((v) => !v)}>پیامک</button>
          <button type="button" className={visitSms ? "board-chip is-active" : "board-chip"} onClick={() => setVisitSms((v) => !v)}>پیامک هنگام ثبت ویزیت</button>
        </div>
        <div className="form-grid">
          <Field label="روز جلوتر" value={days} onChange={setDays} />
          <Field label="ساعت ارسال" value={time} onChange={setTime} />
        </div>
        <PrimaryButton onClick={() => void updateCommunications(session, { reminders_enabled: reminders, sms_enabled: sms, days_ahead: Number(days) || 1, send_time: time, visit_sms_on_booking: visitSms }).catch((err) => catchErr(err, "ذخیره نشد."))}>ذخیره</PrimaryButton>
      </div>
    </div>
  );
}

export function BrandScreen({ session }: { session: Session }) {
  const { error, catchErr } = useError();
  const [doctor, setDoctor] = useState("");
  const [phone, setPhone] = useState("");
  useEffect(() => { void loadBrand(session).then((data) => { setDoctor(data.doctor_name || ""); setPhone(data.phone || ""); }); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">برند و چاپ</h1>
      <ErrorBanner message={error} />
      <div className="panel form-stack">
        <Field label="نام پزشک" value={doctor} onChange={setDoctor} />
        <Field label="تلفن مطب" value={phone} onChange={setPhone} />
        <PrimaryButton onClick={() => void updateBrand(session, { doctor_name: doctor, phone }).catch((err) => catchErr(err, "برند ذخیره نشد."))}>ذخیره برند</PrimaryButton>
      </div>
    </div>
  );
}

export function AuditScreen({ session }: { session: Session }) {
  const [logs, setLogs] = useState<Array<{ id: number; action?: string; user?: string; subject?: string; jalali?: string }>>([]);
  useEffect(() => { void loadActivityLogs(session).then((data) => setLogs(data.logs || [])); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">ممیزی تغییرات</h1>
      <div className="stack">
        {logs.map((row) => (
          <article key={row.id} className="card">
            <strong>{row.action}</strong>
            <p className="meta">{joinMeta([row.user, row.subject, row.jalali])}</p>
          </article>
        ))}
      </div>
    </div>
  );
}

export function HisScreen({ session }: { session: Session }) {
  const [rows, setRows] = useState<Array<{ resource?: string; imported?: number; updated_at?: string }>>([]);
  useEffect(() => { void loadHis(session).then((data) => setRows(data.rows || [])); }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">همگام HIS</h1>
      <div className="stack">
        {rows.map((row) => (
          <article key={row.resource} className="card">
            <strong>{row.resource}</strong>
            <p className="meta">{joinMeta([`وارد شده ${row.imported ?? 0}`, row.updated_at])}</p>
          </article>
        ))}
      </div>
      {rows.length === 0 ? <p className="hint">وضعیت همگام HIS روی این هاست ثبت نشده است.</p> : null}
    </div>
  );
}
