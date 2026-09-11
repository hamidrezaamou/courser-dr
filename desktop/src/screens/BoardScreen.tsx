import { useEffect, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { BoardAppointmentCard } from "../components/BookingCards";
import type { BookingNav } from "../components/RowToolbox";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, loadBoard } from "../lib/api";
import { overlayBoard, subscribeOutbox } from "../lib/outbox";
import { sendReminders, toggleSms } from "../lib/ops";
import type { BookingDto, BoardResponse, Session } from "../lib/types";
import { ChecklistDrawer, ReadyAnswersPanel, targetFromBooking } from "./ClinicToolsPages";

export function BoardScreen({
  session,
  nav,
}: {
  session: Session;
  nav: BookingNav;
}) {
  const [kind, setKind] = useState<"visit" | "surgery">("surgery");
  const [date, setDate] = useState<string | null>(null);
  const [hospitalId, setHospitalId] = useState<number | null>(null);
  const [data, setData] = useState<BoardResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [remindersOn, setRemindersOn] = useState(true);
  const [openRem, setOpenRem] = useState<number | null>(null);
  const [readyFor, setReadyFor] = useState<BookingDto | null>(null);
  const [checkFor, setCheckFor] = useState<number | null>(null);

  async function refresh() {
    const result = await loadBoard(session, kind, date, hospitalId);
    setData(result.data);
    if (!date && result.data.date) setDate(result.data.date);
    if (result.data.board_reminders_enabled === false) setRemindersOn(false);
  }

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        await refresh();
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "نوبت‌ها بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [session, kind, date, hospitalId]);

  useEffect(() => subscribeOutbox(() => {
    setData((current) => (current ? overlayBoard(current) : current));
  }), []);

  const stats = data?.stats;
  const dayLabel = data?.date && data.date === data.today ? "امروز" : "این روز";
  const showReminders = remindersOn && data?.board_reminders_enabled !== false;
  const reminders = data?.reminder_items || [];

  function Filters({ compact = false }: { compact?: boolean }) {
    return (
      <div className={compact ? "board-filters board-filters--sheet" : "board-filters"}>
        <div className="board-filters__date">
          <span className="board-filters__label">تاریخ شمسی</span>
          <div className="board-filters__date-row">
            <label className="board-date">
              <input value={date || ""} onChange={(event) => setDate(event.target.value || null)} placeholder="۱۴۰۴/۰۶/۲۰" />
            </label>
            {data?.today ? (
              <button className={date === data.today ? "board-chip is-active" : "board-chip"} type="button" onClick={() => setDate(data.today || null)}>امروز</button>
            ) : null}
            {data?.tomorrow ? (
              <button className={date === data.tomorrow ? "board-chip is-active" : "board-chip"} type="button" onClick={() => setDate(data.tomorrow || null)}>فردا</button>
            ) : null}
          </div>
        </div>
        <label className="board-filters__field">
          <span className="board-filters__label">نوع</span>
          <select
            className="field-input"
            value={kind}
            onChange={(event) => {
              setKind(event.target.value as "visit" | "surgery");
              setHospitalId(null);
            }}
          >
            <option value="surgery">عمل</option>
            <option value="visit">ویزیت</option>
          </select>
        </label>
        {kind === "surgery" ? (
          <label className="board-filters__field">
            <span className="board-filters__label">بیمارستان</span>
            <select
              className="field-input"
              value={hospitalId || ""}
              onChange={(event) => setHospitalId(event.target.value ? Number(event.target.value) : null)}
            >
              <option value="">بیمارستان</option>
              {(data?.hospitals || []).map((hospital) => (
                <option key={hospital.id} value={hospital.id}>{hospital.name}</option>
              ))}
            </select>
          </label>
        ) : null}
        <div className="board-filters__actions">
          <button type="button" className="btn-secondary btn-compact" onClick={() => { setDate(data?.today || null); setHospitalId(null); setKind("surgery"); }}>حذف</button>
        </div>
      </div>
    );
  }

  return (
    <PageChrome
      bodyClass="board-page"
      padded={false}
      header={
        <div className="board-hero">
          <h2 className="board-hero__title">نوبت‌های فعال</h2>
          {stats ? (
            <div className="board-stats">
              <div className="board-stat">کل نوبت‌های {dayLabel}: <strong>{stats.total ?? 0}</strong></div>
              <div className="board-stat board-stat--ok">تایید شده: <strong>{stats.confirmed ?? 0}</strong></div>
              <div className="board-stat">انجام‌شده: <strong>{stats.done ?? 0}</strong></div>
              <div className="board-stat board-stat--warn">عدم حضور: <strong>{stats.no_show ?? 0}</strong></div>
              <div className="board-stat board-stat--danger">لغو شده: <strong>{stats.cancelled ?? 0}</strong></div>
              {data?.approval_enabled && (stats.pending_approval || 0) > 0 ? (
                <div className="board-stat board-stat--warn">در انتظار تأیید: <strong>{stats.pending_approval}</strong></div>
              ) : null}
            </div>
          ) : null}
        </div>
      }
    >
      <div className="space-y-4">
      <section className="board-panel board-panel--filters">
        <div className="board-mobile-bar sm:hidden">
          <div className="board-mobile-bar__date">
            <span className="board-mobile-bar__label" dir="ltr">{date || data?.date}</span>
            <div className="board-date-quick">
              {data?.today ? <button className={date === data.today ? "board-chip is-active" : "board-chip"} type="button" onClick={() => setDate(data.today || null)}>امروز</button> : null}
              {data?.tomorrow ? <button className={date === data.tomorrow ? "board-chip is-active" : "board-chip"} type="button" onClick={() => setDate(data.tomorrow || null)}>فردا</button> : null}
            </div>
          </div>
          <button type="button" className="board-mobile-bar__filter" onClick={() => setFiltersOpen(true)}>فیلترها</button>
        </div>
        <div className="hidden sm:block">
          <Filters />
        </div>
      </section>

      {data?.board_reminders_enabled !== false ? (
        <section className="board-remind-bar">
          <div className="board-remind-bar__copy">
            <div className="board-remind-bar__title-row">
              <h3 className="board-remind-bar__title">یادآوری پیامک</h3>
              <label className="board-toggle" title="نمایش ستون یادآوری">
                <input
                  type="checkbox"
                  className="sr-only"
                  checked={remindersOn}
                  onChange={(event) => setRemindersOn(event.target.checked)}
                />
                <span className="board-toggle__track" />
              </label>
            </div>
            <p className="board-remind-bar__hint">
              {data?.sms_enabled
                ? <>ارسال پیامک یادآوری برای <strong>همه روزها</strong> فعال است.</>
                : <span className="board-remind-bar__state">ارسال پیامک یادآوری برای همه روزها متوقف شده است.</span>}
              {data?.sms_live ? <span className="board-remind-bar__state is-on"> درایور SMS.ir فعال است.</span> : null}
              {data?.reminders_enabled === false ? <span className="board-remind-bar__state"> یادآوری خودکار در تنظیمات غیرفعال است.</span> : null}
            </p>
          </div>
          <div className="board-filters__actions">
            {session.user.can_manage_settings ? (
              <button
                type="button"
                className={data?.sms_enabled ? "board-chip is-active" : "board-chip"}
                onClick={() => void toggleSms(session).then((result) => { setNotice(result.message || null); return refresh(); })}
              >
                پیامک {data?.sms_enabled ? "روشن" : "خاموش"}
              </button>
            ) : null}
            {data?.reminders_enabled !== false ? (
              <button
                type="button"
                className="btn-primary board-remind-bar__btn"
                onClick={() => {
                  if (!window.confirm(`یادآوری برای تاریخ ${date || data?.date || ""} ارسال شود؟`)) return;
                  void sendReminders(session, date || undefined)
                    .then((result) => { setNotice(result.message || "یادآوری ارسال شد."); setError(null); })
                    .catch((err) => setError(err instanceof Error ? err.message : "یادآوری ارسال نشد."));
                }}
              >
                ارسال یادآوری دستی
              </button>
            ) : null}
          </div>
        </section>
      ) : null}

      <ErrorBanner message={error} />
      {notice ? <p className="hint">{notice}</p> : null}
      {loading && !data ? <LoadingBox /> : null}

      <div className={`board-split${showReminders ? "" : " board-split--solo"}`}>
        <section className="board-panel board-col board-col--schedule">
          <div className="board-panel__head">
            <h3 className="board-panel__title">
              برنامه <span className="board-panel__date" dir="ltr">{date || data?.date}</span>
            </h3>
            <span className="board-count">{(data?.items || []).length}</span>
          </div>
          {(data?.items || []).length === 0 && !loading ? (
            <p className="board-empty">برای این فیلتر نوبتی پیدا نشد.</p>
          ) : (
            <div className="board-schedule-list">
              {(data?.items || []).map((item, index) => (
                <BoardAppointmentCard
                  key={`${item.kind}-${item.id}`}
                  item={item}
                  index={index}
                  session={session}
                  nav={nav}
                  onReady={() => setReadyFor(item)}
                  onChecklist={item.kind === "surgery" ? () => setCheckFor(item.id) : undefined}
                  onApplied={() => void refresh()}
                />
              ))}
            </div>
          )}
        </section>
        {showReminders ? (
          <section className="board-panel board-col board-col--remind">
            <div className="board-panel__head">
              <h3 className="board-panel__title">مدیریت یادآوری‌ها</h3>
              <span className="board-count">{reminders.length}</span>
            </div>
            {reminders.length === 0 ? (
              <p className="board-empty">موردی برای یادآوری در این روز نیست.</p>
            ) : (
              <div className="board-remind-list">
                {reminders.map((rem, index) => (
                  <article key={`${rem.mobile}-${index}`} className={`board-remind-card${openRem === index ? " is-open" : ""}`}>
                    <div className="board-remind-card__bar" role="button" tabIndex={0} onClick={() => setOpenRem((current) => (current === index ? null : index))}>
                      <div className="board-remind-card__bar-main min-w-0">
                        <span
                          className="board-remind-card__name"
                          role="button"
                          tabIndex={0}
                          onClick={(event) => {
                            event.stopPropagation();
                            if (rem.booking) setReadyFor(rem.booking);
                          }}
                        >
                          {rem.name}
                          <span className="board-remind-card__time" dir="ltr">({rem.time})</span>
                        </span>
                        <span className="board-remind-card__note">{rem.label}{rem.kind ? ` · ${rem.kind}` : ""}</span>
                      </div>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" className="board-remind-card__chevron" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                    </div>
                    {openRem === index ? (
                      <div className="board-remind-card__drawer">
                        <p className="board-remind-card__meta ltr-data"><span dir="ltr">{rem.mobile || "—"}</span></p>
                        <div className="board-remind-card__actions">
                          <button type="button" className="btn-secondary btn-compact" onClick={() => rem.booking && setReadyFor(rem.booking)}>پاسخ آماده</button>
                        </div>
                      </div>
                    ) : null}
                  </article>
                ))}
              </div>
            )}
          </section>
        ) : null}
      </div>

      {filtersOpen ? (
        <div className="filter-sheet" onClick={() => setFiltersOpen(false)} role="presentation">
          <div className="filter-sheet__panel" onClick={(event) => event.stopPropagation()}>
            <div className="filter-sheet__head">
              <h3>فیلتر نوبت‌ها</h3>
              <button type="button" className="tg-tool" onClick={() => setFiltersOpen(false)}>×</button>
            </div>
            <Filters compact />
          </div>
        </div>
      ) : null}

      {readyFor ? <ReadyAnswersPanel session={session} target={readyFor.id > 0 ? targetFromBooking(readyFor) : undefined} onClose={() => setReadyFor(null)} /> : null}
      {checkFor ? <ChecklistDrawer session={session} surgeryId={checkFor} onClose={() => setCheckFor(null)} /> : null}
      </div>
    </PageChrome>
  );
}
