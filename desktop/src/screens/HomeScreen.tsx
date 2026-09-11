import { useEffect, useState } from "react";
import { CacheHint, Card, EmptyState, ErrorBanner, LoadingBox, StatTile, StatusChip } from "../components/ui";
import { ApiError, loadHome } from "../lib/api";
import { overlayHome, subscribeOutbox } from "../lib/outbox";
import { joinMeta } from "../lib/status";
import type { HomeResponse, Session } from "../lib/types";

export function HomeScreen({
  session,
  onOpenPatient,
}: {
  session: Session;
  onOpenPatient: (id: number) => void;
}) {
  const [data, setData] = useState<HomeResponse | null>(null);
  const [fromCache, setFromCache] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await loadHome(session);
        if (!alive) return;
        setData(result.data);
        setFromCache(result.fromCache);
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "خانه بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session]);

  useEffect(() => subscribeOutbox(() => {
    setData((current) => (current ? overlayHome(current) : current));
  }), []);

  const visits = data?.today_visits?.length ? data.today_visits : data?.upcoming_visits || [];
  const surgeries = data?.today_surgeries?.length ? data.today_surgeries : data?.upcoming_surgeries || [];
  const stats = (data?.stats || []).slice(0, 6);

  return (
    <div className="page">
      <header className="page-head">
        <div>
          <h1>{data?.clinic?.name || "آرشیو بیمار"}</h1>
          <p className="lede">
            {joinMeta([session.user.name, session.user.role_label, data?.today_jalali])}
          </p>
        </div>
      </header>
      <CacheHint fromCache={fromCache} online={!fromCache} />
      <ErrorBanner message={error} />
      {loading && !data ? <LoadingBox /> : null}
      {stats.length > 0 ? (
        <div className="stat-grid">
          {stats.map((stat) => (
            <StatTile key={stat.key || stat.label || ""} label={stat.label || ""} value={stat.value ?? 0} />
          ))}
        </div>
      ) : null}
      <section className="split-2">
        <div>
          <h2>ویزیت‌های امروز</h2>
          {visits.length === 0 && !loading ? <EmptyState text="موردی برای امروز نیست" /> : null}
          <div className="stack">
            {visits.map((item) => (
              <Card key={`v-${item.id}`} onClick={() => item.patient_id && onOpenPatient(item.patient_id)}>
                <div className="row-between">
                  <div>
                    <strong>{item.patient_name || "—"}</strong>
                    <p className="meta">{joinMeta([item.title, item.scheduled_time_label, item.subtitle])}</p>
                  </div>
                  <StatusChip status={item.status} label={item.status_label} />
                </div>
              </Card>
            ))}
          </div>
        </div>
        <div>
          <h2>عمل‌های امروز</h2>
          {surgeries.length === 0 && !loading ? <EmptyState text="موردی برای امروز نیست" /> : null}
          <div className="stack">
            {surgeries.map((item) => (
              <Card key={`s-${item.id}`} onClick={() => item.patient_id && onOpenPatient(item.patient_id)}>
                <div className="row-between">
                  <div>
                    <strong>{item.patient_name || "—"}</strong>
                    <p className="meta">
                      {joinMeta([item.title, item.scheduled_time_label, item.hospital_name, item.eye_side_label])}
                    </p>
                  </div>
                  <StatusChip status={item.status} label={item.status_label} />
                </div>
              </Card>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}
