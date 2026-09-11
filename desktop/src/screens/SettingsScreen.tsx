import { useEffect, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { SettingsDock } from "../components/SettingsDock";
import { loadHome, loadPanel } from "../lib/api";
import { ContactsPane, PatientsManagePane, ProgramsPane } from "./MoreClinicPages";
import { FollowupSettingsPane } from "./FollowupSettingsPane";
import { ChecklistSettingsPane } from "./ClinicToolsPages";
import { DrugsPane, HospitalsPane, TimesPane, TypesPane } from "./SettingsPanes";
import type { Session } from "../lib/types";

type SettingsSection = "times" | "hospitals" | "types" | "drugs" | "programs" | "follow-ups" | "contacts" | "patients" | "checklist";

const SECTION_META: Record<SettingsSection, { title: string; ghost: string; width: "7xl" | "6xl" | "5xl" | "3xl" }> = {
  times: { title: "تایم‌های کاری", ghost: "داشبورد", width: "7xl" },
  hospitals: { title: "بیمارستان‌ها", ghost: "داشبورد", width: "7xl" },
  types: { title: "انواع عمل", ghost: "تایم‌ها", width: "5xl" },
  programs: { title: "گروه‌های برنامه نوبت عمل", ghost: "تایم‌های عمل", width: "5xl" },
  "follow-ups": { title: "الگو و تنظیمات پیگیری", ghost: "داشبورد پیگیری", width: "6xl" },
  contacts: { title: "خروجی مخاطب", ghost: "", width: "3xl" },
  drugs: { title: "فهرست داروها", ghost: "داشبورد", width: "7xl" },
  patients: { title: "مدیریت بیماران", ghost: "", width: "7xl" },
  checklist: { title: "قالب چک‌لیست", ghost: "انواع عمل", width: "5xl" },
};

export function SettingsScreen({
  session,
  section = "times",
  onSection,
  onOpenPatient,
  onDashboard,
  onFollowups,
  onAdmin,
}: {
  session: Session;
  section?: SettingsSection;
  onSection: (section: SettingsSection) => void;
  onOpenPatient?: (id: number) => void;
  onDashboard?: () => void;
  onFollowups?: () => void;
  onAdmin?: () => void;
}) {
  const meta = SECTION_META[section] || SECTION_META.times;
  function onGhost() {
    if (section === "types" || section === "programs") onSection("times");
    else if (section === "follow-ups") onFollowups?.();
    else if (section === "checklist") onSection("types");
    else onDashboard?.();
  }
  return (
    <PageChrome
      width={meta.width}
      bodyClass="settings-page-body"
      padded={false}
      gap="space-y-5"
      header={
        <div className="page-header--compact flex items-center justify-between gap-3">
          <h2 className="page-title">{meta.title}</h2>
          {meta.ghost ? (
            <button type="button" className="btn-ghost hidden sm:inline-flex" onClick={onGhost}>{meta.ghost}</button>
          ) : null}
        </div>
      }
    >
      <SettingsDock section={section} onSection={onSection} canManagePatients={!!session.user.can_manage_settings} />
      {session.user.can_manage_settings && section === "times" ? (
        <div className="mb-3">
          <button type="button" className="admin-panel__link" onClick={() => onAdmin?.()}>← بازگشت به مدیریت کل سایت</button>
        </div>
      ) : null}
      {section === "times" ? <TimesPane session={session} /> : null}
      {section === "hospitals" ? <HospitalsPane session={session} /> : null}
      {section === "types" ? <TypesPane session={session} /> : null}
      {section === "drugs" ? <DrugsPane session={session} /> : null}
      {section === "programs" ? <ProgramsPane session={session} /> : null}
      {section === "follow-ups" ? <FollowupSettingsPane session={session} /> : null}
      {section === "contacts" ? <ContactsPane session={session} /> : null}
      {section === "patients" ? <PatientsManagePane session={session} onOpen={onOpenPatient} /> : null}
      {section === "checklist" ? <ChecklistSettingsPane session={session} /> : null}
    </PageChrome>
  );
}

export function ModulesScreen({
  session,
  onOpen,
}: {
  session: Session;
  onOpen: (module: "waiting" | "accounting" | "approval" | "billing" | "consent" | "portal" | "quality" | "eye_chart" | "rx_print") => void;
}) {
  const [modules, setModules] = useState<Array<{ key: string; label: string; hint?: string }>>([]);
  useEffect(() => {
    void loadPanel(session)
      .then((result) => setModules(result.data.modules || []))
      .catch(() =>
        setModules([
          { key: "accounting", label: "حسابداری", hint: "دفتر روزانه دریافت و بدهکاری" },
          { key: "waiting", label: "لیست انتظار", hint: "صف انتظار عمل و ویزیت" },
          { key: "approval", label: "تأیید نوبت آنلاین", hint: "نوبت‌های در انتظار تأیید" },
          { key: "eye_chart", label: "جدول بینایی", hint: "VA از معاینه پرونده" },
        ]),
      );
  }, [session]);
  return (
    <div className="page">
      <h1 className="page-title">ماژول‌های پیشرفته</h1>
      <div className="hub-grid">
        {modules.map((item) => (
          <button
            key={item.key}
            type="button"
            className="panel hub-card"
            onClick={() => {
              const key = item.key === "rx_print" || item.key === "eye_chart" || item.key === "billing" || item.key === "consent" || item.key === "portal" || item.key === "quality" || item.key === "waiting" || item.key === "accounting" || item.key === "approval" ? item.key : "";
              if (key) onOpen(key);
            }}
          >
            <h3>{item.label}</h3>
            <p className="hint">{item.hint}</p>
          </button>
        ))}
      </div>
    </div>
  );
}

export function AdminScreen({
  session,
  onUsers,
  onSettings,
  onModules,
  onPrints,
  onReports,
  onFeatures,
  onQuickLinks,
  onCommunications,
  onBrand,
  onAudit,
  onHis,
  onSystem,
  onSupport,
}: {
  session: Session;
  onUsers: () => void;
  onSettings: () => void;
  onModules: () => void;
  onPrints: () => void;
  onReports: () => void;
  onFeatures: () => void;
  onQuickLinks: () => void;
  onCommunications: () => void;
  onBrand: () => void;
  onAudit: () => void;
  onHis: () => void;
  onSystem: () => void;
  onSupport: () => void;
}) {
  const [stats, setStats] = useState<Array<{ label?: string | null; value?: number }>>([]);
  useEffect(() => {
    void loadHome(session).then((result) => setStats(result.data.stats || []));
  }, [session]);
  return (
    <div className="page">
      <header className="page-head">
        <div>
          <p className="eyebrow">مرکز فرمان</p>
          <h1 className="page-title">مدیریت کل سایت</h1>
        </div>
        <button type="button" className="btn-primary btn-compact" onClick={onUsers}>حساب و کاربران</button>
      </header>
      <div className="stat-grid">
        {stats.map((stat) => (
          <div key={stat.label || ""} className="stat">
            <strong>{stat.value ?? 0}</strong>
            <span>{stat.label}</span>
          </div>
        ))}
      </div>
      <div className="hub-grid">
        <button type="button" className="panel hub-card" onClick={onSettings}>
          <h3>تنظیمات کلینیک</h3>
          <p className="hint">تایم‌ها، بیمارستان و انواع عمل</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onUsers}>
          <h3>کاربران و نقش‌ها</h3>
          <p className="hint">ساخت و حذف پرسنل</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onFeatures}>
          <h3>قابلیت‌ها</h3>
          <p className="hint">پرچم‌های صف، پیگیری، وایت‌برد و ماژول‌ها</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onQuickLinks}>
          <h3>لینک‌های ویژه هدر</h3>
          <p className="hint">میانبرهای منوی ویژه</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onCommunications}>
          <h3>ارتباطات و پیامک</h3>
          <p className="hint">یادآوری نوبت، پیامک و زمان ارسال</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onBrand}>
          <h3>برند مطب</h3>
          <p className="hint">نام پزشک و تلفن روی برگه‌های چاپ</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onAudit}>
          <h3>ممیزی</h3>
          <p className="hint">لاگ تغییرات پرونده و تنظیمات</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onHis}>
          <h3>همگام HIS</h3>
          <p className="hint">وضعیت واردسازی منابع بیمارستانی</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onSystem}>
          <h3>سیستم و پشتیبان</h3>
          <p className="hint">بک‌آپ، بازیابی و نگه‌داشت داده</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onSupport}>
          <h3>پشتیبانی</h3>
          <p className="hint">تماس، تلگرام و SLA</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onReports}>
          <h3>گزارشات</h3>
          <p className="hint">نوبت ویزیت و عمل با فیلتر روز</p>
        </button>
        <button type="button" className="panel hub-card" onClick={onPrints}>
          <h3>برند و چاپ</h3>
          <p className="hint">پرینت گزارش و بُرد روز</p>
        </button>
        {session.user.can_access_modules ? (
          <button type="button" className="panel hub-card" onClick={onModules}>
            <h3>ماژول‌های پیشرفته</h3>
            <p className="hint">حسابداری، انتظار و ابزارهای فعال کلینیک</p>
          </button>
        ) : null}
      </div>
    </div>
  );
}

export function PrintsScreen({
  session,
  surgeryId,
  onOpenHub,
}: {
  session: Session;
  surgeryId?: number;
  onOpenHub?: (id: number) => void;
}) {
  const [date, setDate] = useState("");
  const [items, setItems] = useState<import("../lib/types").BookingDto[]>([]);
  const [types, setTypes] = useState<Record<string, string>>({});
  const [hub, setHub] = useState<{
    surgery?: import("../lib/types").BookingDto;
    types?: Record<string, string>;
    checklist_warning?: string | null;
  } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!surgeryId) {
      setHub(null);
      return;
    }
    void import("../lib/ops").then(({ loadPrintHub }) =>
      loadPrintHub(session, surgeryId)
        .then(setHub)
        .catch((err) => setError(err instanceof Error ? err.message : "هاب چاپ نیامد.")),
    );
  }, [session, surgeryId]);

  useEffect(() => {
    if (surgeryId) return;
    void import("../lib/ops").then(({ loadPrints }) => loadPrints(session, date).then((data) => {
      if (!date && data.date) setDate(data.date);
      setItems(data.items || []);
      setTypes(data.types || {});
    }).catch(() => undefined));
  }, [session, date, surgeryId]);

  async function printType(id: number, type: string) {
    const { loadPrintSheet, openPrintHtml } = await import("../lib/ops");
    const data = await loadPrintSheet(session, id, type);
    if (data.html) openPrintHtml(data.html);
  }

  async function printAll(id: number) {
    const { loadPrintAll, openPrintHtml } = await import("../lib/ops");
    const data = await loadPrintAll(session, id);
    if (data.html) openPrintHtml(data.html);
  }

  if (surgeryId) {
    const surgery = hub?.surgery;
    const hubTypes = hub?.types || types;
    return (
      <div className="page">
        <header className="page-head">
          <h1 className="page-title">چاپ برگه‌های عمل</h1>
          <button type="button" className="btn-primary btn-compact" onClick={() => void printAll(surgeryId)}>چاپ همه</button>
        </header>
        {error ? <p className="hint">{error}</p> : null}
        {surgery ? (
          <div className="card" style={{ marginBottom: 12 }}>
            <strong>{surgery.patient_name}</strong>
            <p className="meta">{[surgery.title, surgery.hospital_name, surgery.scheduled_date_jalali, surgery.scheduled_time_label].filter(Boolean).join(" · ")}</p>
          </div>
        ) : null}
        {hub?.checklist_warning ? <p className="hint">{hub.checklist_warning}</p> : null}
        <div className="action-row tight">
          {Object.entries(hubTypes).map(([key, label]) => (
            <button key={key} type="button" className="chip-btn" onClick={() => void printType(surgeryId, key)}>{label}</button>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="page">
      <header className="page-head">
        <h1 className="page-title">پرینت‌ها</h1>
        <button type="button" className="btn-primary btn-compact" onClick={() => window.print()}>چاپ فهرست</button>
      </header>
      <label className="field"><span>تاریخ جلالی</span><input value={date} onChange={(event) => setDate(event.target.value)} /></label>
      <p className="hint">برگه‌های معرفی بیمارستان، بیهوشی، آزمایشگاه، IOL و نسخه برای هر نوبت عمل این روز.</p>
      <div className="stack" style={{ marginTop: 12 }}>
        {items.map((item) => (
          <article key={item.id} className="card">
            <strong>{item.patient_name}</strong>
            <p className="meta">{[item.title, item.hospital_name, item.scheduled_time_label].filter(Boolean).join(" · ")}</p>
            <div className="action-row tight">
              <button type="button" className="chip-btn" onClick={() => onOpenHub?.(item.id)}>هاب چاپ</button>
              <button type="button" className="chip-btn" onClick={() => void printAll(item.id)}>چاپ همه</button>
              {Object.entries(types).map(([key, label]) => (
                <button key={key} type="button" className="chip-btn" onClick={() => void printType(item.id, key)}>
                  {label}
                </button>
              ))}
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}
