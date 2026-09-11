import { useEffect, useRef, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { CacheHint, ErrorBanner, LoadingBox } from "../components/ui";
import { RowToolbox } from "../components/RowToolbox";
import { ApiError, loadPatients } from "../lib/api";
import { overlayPatients, subscribeOutbox } from "../lib/outbox";
import type { BookingDto, PageMeta, PatientDto, Session } from "../lib/types";
import { ReadyAnswersPanel } from "../components/ReadyAnswersPanel";

function patientToolboxItem(patient: PatientDto): BookingDto {
  return {
    id: 0,
    kind: "patient",
    patient_id: patient.id,
    patient_name: patient.name,
    national_code: patient.national_code,
    mobile: patient.mobile,
    mobile_secondary: patient.mobile_secondary,
    subtitle: [
      patient.upcoming_visits_count ? `${patient.upcoming_visits_count} ویزیت` : "",
      patient.upcoming_surgeries_count ? `${patient.upcoming_surgeries_count} عمل` : "",
    ].filter(Boolean).join(" · ") || undefined,
  };
}

function faNum(value: number) {
  return value.toLocaleString("fa-IR");
}

export function DashboardScreen({
  session,
  onOpen,
  onCreate,
  onBookVisit,
  onBookSurgery,
}: {
  session: Session;
  onOpen: (id: number) => void;
  onCreate: () => void;
  onBookVisit: (id: number) => void;
  onBookSurgery: (id: number) => void;
}) {
  const [q, setQ] = useState("");
  const [debounced, setDebounced] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<PatientDto[]>([]);
  const [stats, setStats] = useState<{ total?: number; new_today?: number; upcoming_surgery?: number } | null>(null);
  const [meta, setMeta] = useState<PageMeta | null>(null);
  const [fromCache, setFromCache] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toolsPatient, setToolsPatient] = useState<PatientDto | null>(null);
  const [readyPatient, setReadyPatient] = useState<PatientDto | null>(null);
  const searchRef = useRef<HTMLInputElement>(null);
  const [viewMode, setViewMode] = useState<"cards" | "list">(() => {
    try {
      const saved = localStorage.getItem("patientListView");
      return saved === "list" ? "list" : "cards";
    } catch {
      return "cards";
    }
  });

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebounced(q);
      setPage(1);
    }, 280);
    return () => window.clearTimeout(timer);
  }, [q]);

  useEffect(() => {
    const focus = () => searchRef.current?.focus();
    window.addEventListener("focus-dash-search", focus);
    return () => window.removeEventListener("focus-dash-search", focus);
  }, []);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await loadPatients(session, debounced, page);
        if (!alive) return;
        setItems(result.data.patients || []);
        setStats(result.data.stats || null);
        setMeta(result.data.meta || null);
        setFromCache(result.fromCache);
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "جستجو انجام نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, debounced, page]);

  useEffect(() => subscribeOutbox(() => {
    setItems((current) => overlayPatients({ patients: current }).patients || current);
  }), []);

  function persistView(mode: "cards" | "list") {
    setViewMode(mode);
    try { localStorage.setItem("patientListView", mode); } catch { /* ignore */ }
  }

  const lastPage = meta?.last_page || 1;
  const currentPage = meta?.current_page || page;

  return (
    <PageChrome
      bodyClass="dash-page"
      padded={false}
      header={
        <div className="dash-header">
          <h2 className="dash-header__title">جستجوی بیمار</h2>
          <div className="dash-header__actions">
            <button type="button" className="btn-secondary btn-secondary--warm btn-primary--compact" onClick={() => onBookSurgery(0)}>
              ثبت عمل
            </button>
            {session.user.can_edit_patient ? (
              <button type="button" className="btn-primary btn-primary--compact" onClick={onCreate}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.2">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span className="hidden sm:inline">ثبت بیمار</span>
                <span className="sm:hidden">ثبت</span>
              </button>
            ) : null}
          </div>
        </div>
      }
    >
      <div className="dash-search panel fade-up">
        <label className={loading ? "dash-search__wrap is-loading" : "dash-search__wrap"}>
          <svg className="dash-search__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
          </svg>
          <input
            ref={searchRef}
            type="search"
            className="dash-search__input"
            value={q}
            onChange={(event) => setQ(event.target.value)}
            placeholder="نام، کد ملی یا موبایل..."
            autoComplete="off"
            enterKeyHint="search"
          />
          {q ? (
            <button type="button" className="dash-search__clear" onClick={() => setQ("")} aria-label="پاک کردن جستجو">
              <svg className="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                <path strokeLinecap="round" d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          ) : null}
          {loading ? <span className="dash-search__spinner" /> : null}
        </label>
      </div>

      <CacheHint fromCache={fromCache} online={!fromCache} />
      <ErrorBanner message={error} />

      <div className="patient-results panel fade-up-delay">
        <div className="patient-results__head">
          <div className="patient-results__head-row">
            <div className="patient-view-toggle" role="group" aria-label="نوع نمایش پرونده‌ها">
              <button
                type="button"
                className={viewMode === "list" ? "patient-view-toggle__btn is-active" : "patient-view-toggle__btn"}
                title="نمای لیست"
                aria-label="نمای لیست"
                onClick={() => persistView("list")}
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z" />
                </svg>
              </button>
              <button
                type="button"
                className={viewMode === "cards" ? "patient-view-toggle__btn is-active" : "patient-view-toggle__btn"}
                title="نمای کارت"
                aria-label="نمای کارت"
                onClick={() => persistView("cards")}
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                </svg>
              </button>
            </div>
            <div className="patient-results__head-main">
              <div className="patient-stats">
                <div className="patient-stat">
                  <span className="patient-stat__value">{faNum(stats?.total ?? items.length)}</span>
                  <span className="patient-stat__label">پرونده</span>
                </div>
                <div className="patient-stat patient-stat--accent">
                  <span className="patient-stat__value">{faNum(stats?.new_today ?? 0)}</span>
                  <span className="patient-stat__label">پرونده جدید امروز</span>
                </div>
                <div className="patient-stat">
                  <span className="patient-stat__value">{faNum(stats?.upcoming_surgery ?? 0)}</span>
                  <span className="patient-stat__label">عمل پیش‌رو</span>
                </div>
              </div>
              {debounced ? (
                <p className="patient-results__query mt-2 text-xs font-bold" style={{ color: "var(--brand-dark)" }}>
                  نتیجه جستجو · «{debounced}»
                </p>
              ) : null}
            </div>
          </div>
        </div>
        {loading && items.length === 0 ? <LoadingBox /> : null}
        {!loading && items.length === 0 ? (
          <div className="patient-empty">
            <div className="patient-empty__icon">
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
              </svg>
            </div>
            <p className="patient-empty__title">بیماری یافت نشد</p>
            <p className="patient-empty__hint">نام، کد ملی یا موبایل دیگری امتحان کنید.</p>
          </div>
        ) : null}
        {items.length > 0 ? (
          <>
            <div className="patient-view patient-view--cards" hidden={viewMode !== "cards"}>
              <div className="patient-grid grid grid-cols-1 gap-3 p-3 md:grid-cols-2 md:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                {items.map((patient) => {
                  const initial = (patient.initial || patient.name || "؟").slice(0, 1);
                  return (
                    <article key={patient.id} className="patient-card">
                      <span className="patient-card__corner">{initial}</span>
                      <div className="patient-card__top">
                        <div className="patient-card__info">
                          <h4 className="patient-card__name">{patient.name || "—"}</h4>
                          <p className="patient-card__code ltr-data" dir="ltr">{patient.national_code}</p>
                          <p className="patient-card__phone ltr-data" dir="ltr">{patient.mobile}</p>
                        </div>
                        <div className="patient-card__avatar" aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                          </svg>
                        </div>
                      </div>
                      <div className="patient-card__divider" />
                      <div className="patient-card__foot">
                        <div className="patient-card__actions">
                          <button type="button" className="patient-card__act patient-card__act--tool" title="ابزار" aria-label="ابزار" onClick={() => setToolsPatient(patient)}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>
                          </button>
                          <button type="button" className="patient-card__act patient-card__act--edit" title="ثبت ویزیت" aria-label="ثبت ویزیت" onClick={() => onBookVisit(patient.id)}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                            </svg>
                          </button>
                          <button type="button" className="patient-card__act patient-card__act--file" title="پرونده بیمار" aria-label="پرونده بیمار" onClick={() => onOpen(patient.id)}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                          </button>
                        </div>
                        <span className="patient-card__surgery-badge">تعداد اعمال: {faNum(patient.surgeries_count ?? 0)}</span>
                      </div>
                    </article>
                  );
                })}
              </div>
            </div>
            <div className="patient-view patient-view--list" hidden={viewMode !== "list"}>
              <div className="patient-list flex flex-col">
                {items.map((patient) => (
                  <div
                    key={patient.id}
                    role="button"
                    tabIndex={0}
                    className="patient-row flex w-full cursor-pointer items-center gap-3 border-0 border-b px-4 py-3 text-right transition last:border-b-0"
                    style={{ borderColor: "var(--line)", color: "inherit" }}
                    onClick={() => setToolsPatient(patient)}
                    onKeyDown={(event) => {
                      if (event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        setToolsPatient(patient);
                      }
                    }}
                  >
                    <div className="patient-row__avatar flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-extrabold text-white shadow-sm">
                      {(patient.initial || patient.name || "؟").slice(0, 1)}
                    </div>
                    <div className="patient-row__body min-w-0 flex-1">
                      <div className="patient-row__name truncate text-sm font-bold" style={{ color: "var(--ink)" }}>{patient.name || "—"}</div>
                      <div className="patient-row__meta mt-0.5 truncate text-[11px]" style={{ color: "var(--muted)" }}>
                        <span dir="ltr">{patient.national_code}</span>
                        <span className="patient-row__dot mx-1 opacity-50">·</span>
                        <span dir="ltr">{patient.mobile}</span>
                      </div>
                    </div>
                    <div className="patient-row__badges hidden shrink-0 flex-wrap gap-1 sm:flex">
                      {(patient.upcoming_visits_count || 0) > 0 ? (
                        <span className="patient-badge patient-badge--visit rounded-full px-2 py-0.5 text-[10px] font-extrabold">
                          {patient.upcoming_visits_count} ویزیت
                        </span>
                      ) : null}
                      {(patient.upcoming_surgeries_count || 0) > 0 ? (
                        <span className="patient-badge patient-badge--surgery rounded-full px-2 py-0.5 text-[10px] font-extrabold">
                          {patient.upcoming_surgeries_count} عمل
                        </span>
                      ) : null}
                    </div>
                    <svg className="patient-row__chevron h-4 w-4 shrink-0 opacity-50" style={{ color: "var(--muted)" }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                  </div>
                ))}
              </div>
            </div>
            {lastPage > 1 ? (
              <div className="patient-results__pagination">
                <div className="flex items-center justify-center gap-2">
                  <button type="button" className="btn-secondary btn-compact" disabled={currentPage <= 1 || loading} onClick={() => setPage((current) => Math.max(1, current - 1))}>
                    قبلی
                  </button>
                  <span className="text-xs font-bold" style={{ color: "var(--muted)" }}>
                    صفحه {faNum(currentPage)} از {faNum(lastPage)}
                  </span>
                  <button type="button" className="btn-secondary btn-compact" disabled={currentPage >= lastPage || loading} onClick={() => setPage((current) => current + 1)}>
                    بعدی
                  </button>
                </div>
              </div>
            ) : null}
          </>
        ) : null}
      </div>
      {toolsPatient ? (
        <RowToolbox
          session={session}
          item={patientToolboxItem(toolsPatient)}
          mode="board"
          onClose={() => setToolsPatient(null)}
          nav={{
            onOpenPatient: onOpen,
            onBookVisit: (id) => onBookVisit(id || toolsPatient.id),
            onBookSurgery: (id) => onBookSurgery(id || toolsPatient.id),
          }}
          onReady={() => {
            setReadyPatient(toolsPatient);
            setToolsPatient(null);
          }}
        />
      ) : null}
      {readyPatient ? (
        <ReadyAnswersPanel
          session={session}
          target={{
            patientId: readyPatient.id,
            patientName: readyPatient.name,
            mobile: readyPatient.mobile,
            nationalCode: readyPatient.national_code,
            mobileSecondary: readyPatient.mobile_secondary,
          }}
          onClose={() => setReadyPatient(null)}
        />
      ) : null}
    </PageChrome>
  );
}

export { DashboardScreen as PatientsScreen };
