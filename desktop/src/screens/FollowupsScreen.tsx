import { FormEvent, useEffect, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, loadFollowups } from "../lib/api";
import { followupAction } from "../lib/ops";
import type { FollowUpItem, FollowupOption, Session } from "../lib/types";

const BUCKETS = [
  ["due", "باید پیگیری شوند"],
  ["today", "امروز"],
  ["overdue", "عقب‌افتاده"],
  ["upcoming", "آینده"],
  ["in_progress", "در حال انجام"],
  ["done", "انجام‌شده امروز"],
  ["all", "همه"],
] as const;

const EMPTY_FILTERS = {
  q: "",
  hospital_id: "",
  kind: "",
  method: "",
  assigned_to: "",
  surgery_type_id: "",
  status: "",
  done: "",
  source: "",
  from: "",
  to: "",
};

function toneColor(display?: string | null) {
  return display === "overdue"
    ? "#b91c1c"
    : display === "due"
      ? "#b45309"
      : display === "in_progress"
        ? "#1d4ed8"
        : display === "done"
          ? "#047857"
          : display === "cancelled" || display === "rejected" || display === "failed"
            ? "var(--muted)"
            : "var(--ink)";
}

export function FollowupsScreen({
  session,
  onOpen,
  onSettings,
}: {
  session: Session;
  onOpen: (id: number) => void;
  onSettings?: () => void;
}) {
  const [bucket, setBucket] = useState("due");
  const [filters, setFilters] = useState(EMPTY_FILTERS);
  const [applied, setApplied] = useState(EMPTY_FILTERS);
  const [sheetOpen, setSheetOpen] = useState(false);
  const [items, setItems] = useState<FollowUpItem[]>([]);
  const [counts, setCounts] = useState<Record<string, number>>({});
  const [kinds, setKinds] = useState<FollowupOption[]>([]);
  const [methods, setMethods] = useState<FollowupOption[]>([]);
  const [outcomes, setOutcomes] = useState<FollowupOption[]>([]);
  const [statuses, setStatuses] = useState<FollowupOption[]>([]);
  const [hospitals, setHospitals] = useState<Array<{ id: number; name?: string }>>([]);
  const [surgeryTypes, setSurgeryTypes] = useState<Array<{ id: number; name?: string }>>([]);
  const [staff, setStaff] = useState<Array<{ id: number; name?: string }>>([]);
  const [available, setAvailable] = useState(true);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tick, setTick] = useState(0);

  const activeFilterCount = Object.values(applied).filter(Boolean).length;

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await loadFollowups(session, bucket, applied);
        if (!alive) return;
        setItems(result.data.items || []);
        setCounts(result.data.counts || {});
        setAvailable(result.data.available !== false);
        setKinds(result.data.kinds || []);
        setMethods(result.data.methods || []);
        setOutcomes(result.data.outcomes || []);
        setStatuses(result.data.statuses || []);
        setHospitals(result.data.hospitals || []);
        setSurgeryTypes(result.data.surgery_types || []);
        setStaff(result.data.staff || []);
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "پیگیری بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, bucket, applied, tick]);

  function applyFilters(event?: FormEvent) {
    event?.preventDefault();
    setApplied({ ...filters });
    setSheetOpen(false);
  }

  function clearFilters() {
    setFilters(EMPTY_FILTERS);
    setApplied(EMPTY_FILTERS);
  }

  const filterFields = (
    <>
      <label className="report-filters__field">
        <span>جستجو</span>
        <input className="field-input" placeholder="نام / موبایل / کد ملی" value={filters.q} onChange={(event) => setFilters({ ...filters, q: event.target.value })} />
      </label>
      <label className="report-filters__field">
        <span>بیمارستان</span>
        <select className="field-input" value={filters.hospital_id} onChange={(event) => setFilters({ ...filters, hospital_id: event.target.value })}>
          <option value="">همه بیمارستان‌ها</option>
          {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>نوع پیگیری</span>
        <select className="field-input" value={filters.kind} onChange={(event) => setFilters({ ...filters, kind: event.target.value })}>
          <option value="">همه</option>
          {kinds.map((item) => <option key={item.slug} value={item.slug}>{item.label}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>روش</span>
        <select className="field-input" value={filters.method} onChange={(event) => setFilters({ ...filters, method: event.target.value })}>
          <option value="">همه</option>
          {methods.map((item) => <option key={item.slug} value={item.slug}>{item.label}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>مسئول</span>
        <select className="field-input" value={filters.assigned_to} onChange={(event) => setFilters({ ...filters, assigned_to: event.target.value })}>
          <option value="">همه</option>
          <option value="me">من</option>
          <option value="none">بدون مسئول</option>
          {staff.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>نوع عمل</span>
        <select className="field-input" value={filters.surgery_type_id} onChange={(event) => setFilters({ ...filters, surgery_type_id: event.target.value })}>
          <option value="">همه</option>
          {surgeryTypes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>وضعیت</span>
        <select className="field-input" value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })}>
          <option value="">همه</option>
          {statuses.map((item) => <option key={item.slug} value={item.slug}>{item.label}</option>)}
        </select>
      </label>
      <label className="report-filters__field">
        <span>انجام</span>
        <select className="field-input" value={filters.done} onChange={(event) => setFilters({ ...filters, done: event.target.value })}>
          <option value="">همه</option>
          <option value="0">انجام‌نشده</option>
          <option value="1">انجام‌شده</option>
        </select>
      </label>
      <label className="report-filters__field">
        <span>منبع</span>
        <select className="field-input" value={filters.source} onChange={(event) => setFilters({ ...filters, source: event.target.value })}>
          <option value="">همه</option>
          <option value="template">از الگو</option>
          <option value="reminder">یادآوری مراجعه</option>
          <option value="manual">دستی</option>
          <option value="retry">پیگیری مجدد</option>
        </select>
      </label>
      <label className="report-filters__field report-filters__field--date">
        <span>از تاریخ</span>
        <input className="field-input" placeholder="از تاریخ" value={filters.from} onChange={(event) => setFilters({ ...filters, from: event.target.value })} />
      </label>
      <label className="report-filters__field report-filters__field--date">
        <span>تا تاریخ</span>
        <input className="field-input" placeholder="تا تاریخ" value={filters.to} onChange={(event) => setFilters({ ...filters, to: event.target.value })} />
      </label>
    </>
  );

  return (
    <PageChrome
      header={
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="page-title">پیگیری بیماران</h2>
          {session.user.can_access_clinic_settings ? (
            <button type="button" className="btn-ghost !py-1.5 !text-xs" onClick={onSettings}>الگوها و تنظیمات</button>
          ) : null}
        </div>
      }
    >
      {!available ? <p className="hint">فهرست امروز از نوبت‌های فعال آمده؛ داشبورد کامل پیگیری بعد از به‌روزرسانی هاست فعال می‌شود.</p> : null}
      <div className="report-chips report-no-print">
        {BUCKETS.map(([value, label]) => (
          <button key={value} type="button" className={bucket === value ? "report-chip is-active" : "report-chip"} onClick={() => setBucket(value)}>
            {label} {counts[value] != null ? counts[value] : ""}
          </button>
        ))}
        <button type="button" className={`report-chip report-chip--sheet sm:hidden ${activeFilterCount ? "is-active" : ""}`} onClick={() => setSheetOpen(true)}>
          فیلترها{activeFilterCount ? ` ${activeFilterCount}` : ""}
        </button>
        {activeFilterCount ? (
          <button type="button" className="report-chip report-chip--ghost" onClick={clearFilters}>حذف فیلتر</button>
        ) : null}
      </div>

      <form className="panel p-4 report-no-print hidden sm:block" onSubmit={applyFilters}>
        <div className="report-filters__inline">
          {filterFields}
          <div className="report-filters__field" style={{ justifyContent: "end" }}>
            <span className="opacity-0 pointer-events-none">اعمال</span>
            <div className="flex flex-wrap gap-2">
              <button type="submit" className="btn-primary !py-1.5 !text-xs">اعمال فیلتر</button>
              <button type="button" className="btn-secondary !py-1.5 !text-xs" onClick={clearFilters}>پاک کردن</button>
            </div>
          </div>
        </div>
      </form>

      {sheetOpen ? (
        <div className="filter-sheet sm:hidden" onClick={() => setSheetOpen(false)} role="presentation">
          <div className="filter-sheet__panel" onClick={(event) => event.stopPropagation()} role="dialog">
            <div className="filter-sheet__head">
              <h3>فیلتر پیگیری</h3>
              <button type="button" className="tg-tool" onClick={() => setSheetOpen(false)} aria-label="بستن">×</button>
            </div>
            <form className="space-y-3 report-filter-compact" onSubmit={applyFilters}>
              {filterFields}
              <div className="flex gap-2 pt-1">
                <button type="submit" className="btn-primary flex-1 !py-2 !text-xs">بستن و اعمال</button>
                <button type="button" className="btn-ghost !py-2 !text-xs" onClick={clearFilters}>حذف</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      <ErrorBanner message={error} />
      {loading ? <LoadingBox /> : null}
      <div className="space-y-2">
        {items.map((item) => (
          <FollowupCard
            key={item.id}
            session={session}
            item={item}
            outcomes={outcomes}
            staff={staff}
            onOpen={onOpen}
            onDone={() => setTick((value) => value + 1)}
          />
        ))}
      </div>
      {!loading && items.length === 0 ? <div className="panel p-6 text-sm" style={{ color: "var(--muted)" }}>پیگیری‌ای در این نما نیست.</div> : null}
    </PageChrome>
  );
}

function FollowupCard({
  session,
  item,
  outcomes,
  staff,
  onOpen,
  onDone,
}: {
  session: Session;
  item: FollowUpItem;
  outcomes: FollowupOption[];
  staff: Array<{ id: number; name?: string }>;
  onOpen: (id: number) => void;
  onDone: () => void;
}) {
  const [openResult, setOpenResult] = useState(false);
  const [openRetry, setOpenRetry] = useState(false);
  const [outcome, setOutcome] = useState("");
  const [notes, setNotes] = useState("");
  const [daysAfter, setDaysAfter] = useState("2");
  const [retryDate, setRetryDate] = useState("");
  const [assigned, setAssigned] = useState("");
  const [retryNotes, setRetryNotes] = useState("");
  const meta = [
    item.patient_name,
    item.patient_mobile,
    item.surgery_type_name ? `${item.surgery_type_name}${item.surgery_subtype_name ? ` · ${item.surgery_subtype_name}` : ""}` : "",
    item.hospital_name ? `بیمارستان ${item.hospital_name}` : "",
  ].filter(Boolean).join(" · ");

  return (
    <article className="panel space-y-3 p-4">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>{item.title}</h3>
            <span className="rounded-full px-2 py-0.5 text-[10px] font-extrabold" style={{ background: "var(--panel-soft)", color: toneColor(item.display_status) }}>{item.status_label}</span>
            {item.source_label ? <span className="text-[10px] font-bold" style={{ color: "var(--muted)" }}>{item.source_label}</span> : null}
          </div>
          <div className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>
            {item.patient_id ? (
              <button type="button" className="font-bold" style={{ color: "var(--brand-dark)" }} onClick={() => onOpen(item.patient_id!)}>{item.patient_name}</button>
            ) : item.patient_name}
            {meta.replace(item.patient_name || "", "").replace(/^ · /, "") ? <> · {meta.replace(`${item.patient_name} · `, "")}</> : null}
          </div>
          <div className="mt-1 text-[11px] font-bold" dir="ltr" style={{ color: "var(--ink)" }}>{item.due_jalali}</div>
          <div className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>
            {[item.kind_label, item.method_label, item.assignee_name ? `مسئول: ${item.assignee_name}` : "بدون مسئول", item.parent_id ? `ادامه #${item.parent_id}` : ""].filter(Boolean).join(" · ")}
          </div>
          {item.description ? <p className="mt-2 text-xs leading-6" style={{ color: "var(--ink)" }}>{item.description}</p> : null}
          {item.outcome_label ? <p className="mt-1 text-[11px] font-bold" style={{ color: "var(--brand-dark)" }}>نتیجه: {item.outcome_label}</p> : null}
          {item.outcome_notes ? <p className="text-[11px]" style={{ color: "var(--muted)" }}>{item.outcome_notes}</p> : null}
        </div>
        {item.patient_mobile ? (
          <a className="btn-secondary !px-2 !py-1 !text-[10px]" href={`tel:${item.patient_mobile}`} dir="ltr">{item.patient_mobile}</a>
        ) : null}
      </div>
      {item.open !== false && item.id > 0 ? (
        <>
          <div className="flex flex-wrap gap-1">
            {item.status === "pending" ? (
              <button type="button" className="btn-secondary !px-2 !py-1 !text-[10px]" onClick={() => void followupAction(session, item.id, "start").then(onDone)}>شروع</button>
            ) : null}
            <button type="button" className="btn-primary !px-2 !py-1 !text-[10px]" onClick={() => { setOpenResult((value) => !value); setOpenRetry(false); }}>ثبت نتیجه</button>
            <button type="button" className="btn-secondary !px-2 !py-1 !text-[10px]" onClick={() => { setOpenRetry((value) => !value); setOpenResult(false); }}>پیگیری مجدد</button>
            <button type="button" className="btn-secondary !px-2 !py-1 !text-[10px]" style={{ color: "#b91c1c" }} onClick={() => { if (confirm("این پیگیری لغو شود؟")) void followupAction(session, item.id, "cancel").then(onDone); }}>لغو</button>
          </div>
          {openResult ? (
            <form className="space-y-2 rounded-xl border p-3" style={{ borderColor: "var(--line)", background: "var(--panel-soft)" }} onSubmit={(event) => {
              event.preventDefault();
              void followupAction(session, item.id, "complete", { outcome: outcome || "done", outcome_notes: notes }).then(onDone);
            }}>
              <select className="field-input w-full" value={outcome} onChange={(event) => setOutcome(event.target.value)} required>
                <option value="">نتیجه</option>
                {outcomes.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
              </select>
              <textarea rows={2} className="field-input w-full" placeholder="توضیحات نتیجه" value={notes} onChange={(event) => setNotes(event.target.value)} />
              <div className="flex flex-wrap gap-1">
                <button type="submit" className="btn-primary !py-1.5 !text-xs">ذخیره نتیجه</button>
                <button type="button" className="btn-secondary !py-1.5 !text-xs" onClick={() => void followupAction(session, item.id, "fail", { status: "failed" }).then(onDone)}>ناموفق</button>
              </div>
            </form>
          ) : null}
          {openRetry ? (
            <form className="space-y-2 rounded-xl border p-3" style={{ borderColor: "var(--line)", background: "var(--panel-soft)" }} onSubmit={(event) => {
              event.preventDefault();
              void followupAction(session, item.id, "retry", {
                days_after: Number(daysAfter) || 2,
                due_date: retryDate || undefined,
                assigned_to: assigned || undefined,
                notes: retryNotes,
              }).then(onDone);
            }}>
              <div className="grid gap-2 sm:grid-cols-2">
                <select className="field-input" value={daysAfter} onChange={(event) => setDaysAfter(event.target.value)}>
                  <option value="">بعد از چند روز؟</option>
                  <option value="1">۱ روز بعد</option>
                  <option value="2">۲ روز بعد</option>
                  <option value="7">۱ هفته بعد</option>
                  <option value="30">۱ ماه بعد</option>
                </select>
                <input className="field-input" placeholder="یا تاریخ مشخص" value={retryDate} onChange={(event) => setRetryDate(event.target.value)} />
              </div>
              <select className="field-input w-full" value={assigned} onChange={(event) => setAssigned(event.target.value)}>
                <option value="">همان مسئول</option>
                {staff.map((row) => <option key={row.id} value={row.id}>{row.name}</option>)}
              </select>
              <textarea rows={2} className="field-input w-full" placeholder="یادداشت پیگیری مجدد" value={retryNotes} onChange={(event) => setRetryNotes(event.target.value)} />
              <button type="submit" className="btn-primary !py-1.5 !text-xs">ساخت پیگیری بعدی</button>
            </form>
          ) : null}
        </>
      ) : null}
    </article>
  );
}
