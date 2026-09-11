import { useEffect, useMemo, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { ReportAppointmentCard, ReportNotesDialog, ReportTableRow } from "../components/BookingCards";
import type { BookingNav } from "../components/RowToolbox";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, loadReports } from "../lib/api";
import type { BookingDto, HospitalDto, Session } from "../lib/types";
import { STATUS_LABELS } from "../lib/types";
import { ChecklistDrawer, ReadyAnswersPanel, targetFromBooking } from "./ClinicToolsPages";

function savedView(): "list" | "card" {
  try {
    const saved = localStorage.getItem("reports.viewMode");
    if (saved === "card" || saved === "list") return saved;
  } catch {
    /* ignore */
  }
  return window.matchMedia("(max-width: 640px)").matches ? "card" : "list";
}

export function ReportsScreen({
  session,
  nav,
}: {
  session: Session;
  nav: BookingNav;
}) {
  const [kind, setKind] = useState("surgery");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [qInput, setQInput] = useState("");
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [hospitalId, setHospitalId] = useState<number | null>(null);
  const [surgeryTypeId, setSurgeryTypeId] = useState<number | null>(null);
  const [surgerySubtypeId, setSurgerySubtypeId] = useState("");
  const [emergency, setEmergency] = useState(false);
  const [hospitals, setHospitals] = useState<HospitalDto[]>([]);
  const [surgeryTypes, setSurgeryTypes] = useState<Array<{ id: number; name?: string; subtypes?: Array<{ id: number; name?: string }> }>>([]);
  const [statusLabels, setStatusLabels] = useState<Record<string, string>>({ all: "همه وضعیت‌ها", ...STATUS_LABELS });
  const [rows, setRows] = useState<BookingDto[]>([]);
  const [summary, setSummary] = useState({ visit: 0, surgery: 0 });
  const [canExport, setCanExport] = useState(true);
  const [today, setToday] = useState("");
  const [yesterday, setYesterday] = useState("");
  const [tomorrow, setTomorrow] = useState("");
  const [viewMode, setViewMode] = useState<"list" | "card">(savedView);
  const [bulk, setBulk] = useState(false);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [noteFor, setNoteFor] = useState<BookingDto | null>(null);
  const [readyFor, setReadyFor] = useState<BookingDto | null>(null);
  const [checkFor, setCheckFor] = useState<number | null>(null);
  const [filtersOpen, setFiltersOpen] = useState(false);

  useEffect(() => {
    const timer = window.setTimeout(() => setQ(qInput), 350);
    return () => window.clearTimeout(timer);
  }, [qInput]);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await loadReports(session, kind, from, to || from, q, status, hospitalId, {
          emergency,
          surgeryTypeId,
          surgerySubtypeId: surgerySubtypeId || undefined,
        });
        if (!alive) return;
        setRows(result.data.rows || []);
        setToday(result.data.today || "");
        setYesterday(result.data.yesterday || "");
        setTomorrow(result.data.tomorrow || "");
        setHospitals(result.data.hospitals || []);
        setSurgeryTypes(result.data.surgery_types || []);
        if (result.data.status_labels) setStatusLabels(result.data.status_labels);
        setSummary({ visit: result.data.summary?.visit || 0, surgery: result.data.summary?.surgery || 0 });
        setCanExport(result.data.can_export !== false);
        if (!from && result.data.from) {
          setFrom(result.data.from);
          setTo(result.data.to || result.data.from);
        }
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "گزارش بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, kind, from, to, q, status, hospitalId, emergency, surgeryTypeId, surgerySubtypeId]);

  const subtypes = useMemo(
    () => surgeryTypes.find((item) => item.id === surgeryTypeId)?.subtypes || [],
    [surgeryTypes, surgeryTypeId],
  );

  function setView(mode: "list" | "card") {
    setViewMode(mode);
    try { localStorage.setItem("reports.viewMode", mode); } catch { /* ignore */ }
  }

  function keyOf(row: BookingDto) {
    return `${row.kind || "visit"}:${row.id}`;
  }

  function exportCsv() {
    const header = "نوع,نام,کد ملی,موبایل,تاریخ,ساعت,وضعیت,مرکز,جزئیات\n";
    const body = rows
      .map((row) => [row.kind === "surgery" ? "عمل" : "ویزیت", row.patient_name, row.national_code, row.mobile, row.scheduled_date_jalali, row.scheduled_time_label, row.status_label, row.hospital_name, row.title].join(","))
      .join("\n");
    const blob = new Blob(["\uFEFF" + header + body], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "reports.csv";
    link.click();
    URL.revokeObjectURL(url);
  }

  function resetFilters() {
    setKind("surgery");
    setStatus("");
    setHospitalId(null);
    setSurgeryTypeId(null);
    setSurgerySubtypeId("");
    setEmergency(false);
    setQInput("");
    setQ("");
    if (today) {
      setFrom(today);
      setTo(today);
    }
  }

  const dayActive = (value: string) => from === value && to === value;
  const printTitle = emergency
    ? "گزارش نوبت‌ها · اورژانسی"
    : kind === "visit"
      ? "گزارش نوبت‌های ویزیت"
      : kind === "surgery"
        ? "گزارش نوبت‌های عمل"
        : "گزارش نوبت‌ها";

  function FiltersInline() {
    return (
      <div className="report-filters__inline">
        <label className="report-filters__field report-filters__field--date">
          <span>از تاریخ</span>
          <input className="field-input" value={from} onChange={(event) => setFrom(event.target.value)} />
        </label>
        <label className="report-filters__field report-filters__field--date">
          <span>تا تاریخ</span>
          <input className="field-input" value={to} onChange={(event) => setTo(event.target.value)} />
        </label>
        <label className="report-filters__field">
          <span>نوع نوبت</span>
          <select className="field-input" value={kind} disabled={emergency} onChange={(event) => { setKind(event.target.value); if (event.target.value === "visit") { setSurgeryTypeId(null); setSurgerySubtypeId(""); } }}>
            <option value="all">همه</option>
            <option value="visit">ویزیت</option>
            <option value="surgery">عمل</option>
          </select>
        </label>
        <label className="report-filters__field">
          <span>وضعیت</span>
          <select className="field-input" value={status || "all"} onChange={(event) => setStatus(event.target.value === "all" ? "" : event.target.value)}>
            {Object.entries(statusLabels).map(([value, label]) => (
              <option key={value} value={value}>{label}</option>
            ))}
          </select>
        </label>
        <label className="report-filters__field">
          <span>مرکز</span>
          <select className="field-input" value={hospitalId || ""} onChange={(event) => setHospitalId(event.target.value ? Number(event.target.value) : null)}>
            <option value="">همه مراکز</option>
            {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
          </select>
        </label>
        {kind !== "visit" ? (
          <label className="report-filters__field">
            <span>نوع عمل</span>
            <select className="field-input" value={surgeryTypeId || ""} onChange={(event) => { setSurgeryTypeId(event.target.value ? Number(event.target.value) : null); setSurgerySubtypeId(""); }}>
              <option value="">همه انواع</option>
              {surgeryTypes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </label>
        ) : null}
        {kind !== "visit" && surgeryTypeId ? (
          <label className="report-filters__field">
            <span>زیرگروه</span>
            <select className="field-input" value={surgerySubtypeId} onChange={(event) => setSurgerySubtypeId(event.target.value)}>
              <option value="">همه زیرگروه‌ها</option>
              <option value="general">عمومی</option>
              {subtypes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
            </select>
          </label>
        ) : null}
      </div>
    );
  }

  return (
    <PageChrome
      bodyClass={`report-page ${viewMode === "card" ? "report-page--cards" : "report-page--list"}${bulk ? " report-page--bulk" : ""}`}
      header={
        <div className="flex flex-wrap items-center justify-between gap-2 report-no-print">
          <h2 className="page-title !text-base sm:!text-lg">گزارش نوبت‌ها</h2>
          <div className="report-chips">
            {canExport ? <button type="button" className="report-chip" onClick={exportCsv}>خروجی Excel</button> : null}
            <button type="button" className="report-chip" onClick={() => nav.onPrints?.()}>پرینت‌ها</button>
            <button type="button" className="report-chip is-active" onClick={() => window.print()}>چاپ</button>
          </div>
        </div>
      }
    >

      <form className="panel calendar-host report-filters report-no-print" onSubmit={(event) => event.preventDefault()}>
        <div className="report-filters__toolbar">
          <div className="report-filters__search">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" aria-hidden="true">
              <path fillRule="evenodd" d="M9 3.5a5.5 5.5 0 103.39 9.84l3.63 3.64a.75.75 0 101.06-1.06l-3.63-3.64A5.5 5.5 0 009 3.5zM5 9a4 4 0 118 0 4 4 0 01-8 0z" clipRule="evenodd" />
            </svg>
            <input value={qInput} onChange={(event) => setQInput(event.target.value)} placeholder="جستجو نام، موبایل، کد ملی…" />
          </div>
          <div className="report-chips">
            {yesterday ? <button type="button" className={`report-chip${dayActive(yesterday) ? " is-active" : ""}`} onClick={() => { setFrom(yesterday); setTo(yesterday); }}>دیروز</button> : null}
            {today ? <button type="button" className={`report-chip${dayActive(today) ? " is-active" : ""}`} onClick={() => { setFrom(today); setTo(today); }}>امروز</button> : null}
            {tomorrow ? <button type="button" className={`report-chip${dayActive(tomorrow) ? " is-active" : ""}`} onClick={() => { setFrom(tomorrow); setTo(tomorrow); }}>فردا</button> : null}
            <button type="button" className={`report-chip report-chip--danger${emergency ? " is-active" : ""}`} onClick={() => setEmergency((current) => !current)}>
              {emergency ? "اورژانسی ×" : "اورژانس"}
            </button>
            <button type="button" className="report-chip report-chip--sheet" onClick={() => setFiltersOpen(true)}>فیلترها</button>
            <button type="button" className="report-chip report-chip--ghost" onClick={resetFilters}>حذف فیلتر</button>
          </div>
        </div>
        <FiltersInline />
      </form>

      <div className="report-print-header">
        <h1>{printTitle}</h1>
        <div className="subtitle" dir="ltr">{from} — {to}</div>
        <div className="print-date">جمع {rows.length} مورد</div>
      </div>

      <section className="report-table-panel">
        <div className="report-table-toolbar report-no-print">
          <div className="report-counts">
            <span className="report-count is-visit">ویزیت <b>{summary.visit}</b></span>
            <span className="report-count is-surgery">عمل <b>{summary.surgery}</b></span>
          </div>
          <div className="report-table-toolbar__end">
            <button type="button" className={`report-chip${bulk ? " is-active" : ""}`} onClick={() => { setBulk((current) => !current); setSelected(new Set()); }}>
              {bulk ? "پایان انتخاب" : "انتخاب گروهی"}
            </button>
            <div className="patient-view-toggle" role="group" aria-label="نوع نمایش">
              <button type="button" className={`patient-view-toggle__btn${viewMode === "list" ? " is-active" : ""}`} onClick={() => setView("list")} title="نمای لیست">☰</button>
              <button type="button" className={`patient-view-toggle__btn${viewMode === "card" ? " is-active" : ""}`} onClick={() => setView("card")} title="نمای کارت">▦</button>
            </div>
            <span>{rows.length} مورد</span>
          </div>
        </div>
        <ErrorBanner message={error} />
        {loading ? <LoadingBox /> : null}
        {viewMode === "card" ? (
          <div className="report-cards">
            {rows.map((row) => (
              <ReportAppointmentCard
                key={keyOf(row)}
                item={row}
                session={session}
                nav={nav}
                bulk={bulk}
                selected={selected.has(keyOf(row))}
                onToggle={() => setSelected((current) => {
                  const next = new Set(current);
                  const key = keyOf(row);
                  if (next.has(key)) next.delete(key); else next.add(key);
                  return next;
                })}
                onReady={() => setReadyFor(row)}
                onChecklist={row.kind === "surgery" ? () => setCheckFor(row.id) : undefined}
                onNotes={() => setNoteFor(row)}
              />
            ))}
          </div>
        ) : (
          <div className="report-table-wrap">
            <table className="report-table report-table--compact">
              <thead>
                <tr>
                  <th className="col-bulk" />
                  <th className="col-num">#</th>
                  <th className="col-id">ID</th>
                  <th>نوع</th>
                  <th>بیمار</th>
                  <th>کد ملی</th>
                  <th>مرکز / جزئیات</th>
                  <th>تاریخ</th>
                  <th>نوبت/ساعت</th>
                  <th>تلفن</th>
                  <th>وضعیت</th>
                  <th className="col-note">توضیحات</th>
                  <th className="col-actions">عملیات</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row, index) => (
                  <ReportTableRow
                    key={keyOf(row)}
                    item={row}
                    index={index}
                    session={session}
                    nav={nav}
                    bulk={bulk}
                    selected={selected.has(keyOf(row))}
                    onToggle={() => setSelected((current) => {
                      const next = new Set(current);
                      const key = keyOf(row);
                      if (next.has(key)) next.delete(key); else next.add(key);
                      return next;
                    })}
                    onReady={() => setReadyFor(row)}
                    onChecklist={row.kind === "surgery" ? () => setCheckFor(row.id) : undefined}
                    onNotes={() => setNoteFor(row)}
                  />
                ))}
              </tbody>
            </table>
          </div>
        )}
        {!loading && rows.length === 0 ? <p className="pp-empty">موردی یافت نشد.</p> : null}
      </section>

      {bulk && selected.size > 0 ? (
        <div className="report-bulk-bar report-no-print">
          <span>{selected.size} مورد انتخاب شد</span>
          <button
            type="button"
            className="btn-primary btn-compact"
            onClick={() => {
              const first = rows.find((row) => selected.has(keyOf(row)));
              if (first) setReadyFor(first);
            }}
          >
            پاسخ آماده
          </button>
        </div>
      ) : null}

      {filtersOpen ? (
        <div className="filter-sheet" onClick={() => setFiltersOpen(false)} role="presentation">
          <div className="filter-sheet__panel" onClick={(event) => event.stopPropagation()}>
            <div className="filter-sheet__head">
              <h3>فیلتر گزارش</h3>
              <button type="button" className="tg-tool" onClick={() => setFiltersOpen(false)}>×</button>
            </div>
            <FiltersInline />
            <button type="button" className="btn-primary" style={{ marginTop: 12 }} onClick={() => setFiltersOpen(false)}>بستن و اعمال</button>
          </div>
        </div>
      ) : null}

      {noteFor ? <ReportNotesDialog session={session} row={noteFor} onClose={() => setNoteFor(null)} /> : null}
      {readyFor ? <ReadyAnswersPanel session={session} target={targetFromBooking(readyFor)} onClose={() => setReadyFor(null)} /> : null}
      {checkFor ? <ChecklistDrawer session={session} surgeryId={checkFor} onClose={() => setCheckFor(null)} /> : null}
    </PageChrome>
  );
}
