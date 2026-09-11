import { useCallback, useEffect, useState } from "react";
import { PageChrome } from "../components/PageChrome";
import { FloorAppointmentCard } from "../components/BookingCards";
import type { BookingNav } from "../components/RowToolbox";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, loadFloor } from "../lib/api";
import { enqueueStatus, flushOutbox } from "../lib/outbox";
import type { BookingDto, Session } from "../lib/types";
import { ChecklistDrawer, ReadyAnswersPanel, targetFromBooking } from "./ClinicToolsPages";

const COLUMNS: Array<{ key: "upcoming" | "waiting" | "ready" | "in_consult"; title: string; next?: { status: string; label: string } }> = [
  { key: "upcoming", title: "حضور / نوبت روز", next: { status: "waiting", label: "ورود به صف" } },
  { key: "waiting", title: "صف", next: { status: "ready", label: "آماده پزشک" } },
  { key: "ready", title: "ارجاع به پزشک", next: { status: "in_consult", label: "شروع ویزیت" } },
  { key: "in_consult", title: "ویزیت", next: { status: "done", label: "پایان" } },
];

export function FloorScreen({
  session,
  nav,
}: {
  session: Session;
  nav: BookingNav;
}) {
  const [kind, setKind] = useState("all");
  const [date, setDate] = useState<string | null>(null);
  const [cols, setCols] = useState<Record<string, BookingDto[]>>({});
  const [today, setToday] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [readyFor, setReadyFor] = useState<BookingDto | null>(null);
  const [checkFor, setCheckFor] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    const result = await loadFloor(session, kind, date);
    setCols({
      upcoming: result.data.upcoming || [],
      waiting: result.data.waiting || [],
      ready: result.data.ready || [],
      in_consult: result.data.in_consult || [],
    });
    setToday(result.data.today || "");
    if (!date && result.data.date) setDate(result.data.date);
  }, [session, kind, date]);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        await refresh();
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "صف مطب بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    const timer = window.setInterval(() => void refresh().catch(() => undefined), 8000);
    return () => {
      alive = false;
      window.clearInterval(timer);
    };
  }, [refresh]);

  return (
    <PageChrome
      bodyClass="floor-page"
      header={
        <div className="floor-hero">
          <div>
            <h2 className="floor-hero__title">صف مطب</h2>
            <p className="floor-hero__sub">حضور → صف → ارجاع به پزشک → ویزیت → پایان · به‌روزرسانی خودکار</p>
          </div>
          <div className="floor-hero__date">
            <label className="field" style={{ margin: 0 }}>
              <input value={date || ""} onChange={(event) => setDate(event.target.value || null)} placeholder="۱۴۰۴/۰۶/۲۰" />
            </label>
            {today ? (
              <button type="button" className={date === today ? "board-chip is-active" : "board-chip"} onClick={() => setDate(today)}>
                امروز
              </button>
            ) : null}
          </div>
        </div>
      }
    >
      <div className="floor-kind-tabs" role="tablist" aria-label="فیلتر نوع نوبت">
        {[
          ["all", "همه"],
          ["visit", "ویزیت مطب"],
          ["surgery", "عمل بیمارستان"],
        ].map(([value, label]) => (
          <button key={value} type="button" className={kind === value ? "floor-kind-tab is-active" : "floor-kind-tab"} onClick={() => setKind(value)}>
            {label}
          </button>
        ))}
      </div>
      <ErrorBanner message={error} />
      {loading ? <LoadingBox /> : null}
      <div className="floor-grid">
        {COLUMNS.map((column) => (
          <section key={column.key} className={`floor-col floor-col--${column.key === "in_consult" ? "consult" : column.key}`}>
            <h3>
              {column.title} <span>{(cols[column.key] || []).length}</span>
            </h3>
            <div className="stack">
              {(cols[column.key] || []).map((item) => (
                <FloorAppointmentCard
                  key={`${item.kind}-${item.id}`}
                  item={item}
                  session={session}
                  nav={nav}
                  next={column.next}
                  onReady={() => setReadyFor(item)}
                  onChecklist={item.kind === "surgery" ? () => setCheckFor(item.id) : undefined}
                  onApplied={(status) => {
                    if (status) {
                      enqueueStatus(item.kind || "visit", item.id, status, item.status, item.status_label);
                    }
                    void flushOutbox(session).then(() => refresh());
                  }}
                />
              ))}
            </div>
          </section>
        ))}
      </div>
      {readyFor ? <ReadyAnswersPanel session={session} target={targetFromBooking(readyFor)} onClose={() => setReadyFor(null)} /> : null}
      {checkFor ? <ChecklistDrawer session={session} surgeryId={checkFor} onClose={() => setCheckFor(null)} /> : null}
    </PageChrome>
  );
}
