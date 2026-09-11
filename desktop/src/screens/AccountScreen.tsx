import { useState } from "react";
import { ErrorBanner, Field, GhostButton, PrimaryButton } from "../components/ui";
import { logout, updateProfile } from "../lib/api";
import { lastSyncAt, formatClock } from "../lib/cache";
import { dismissOutbox, retryOutbox, useOutbox, type OutboxJob } from "../lib/outbox";
import { normalizeSite, saveSession } from "../lib/session";
import type { Session } from "../lib/types";

function jobLabel(job: OutboxJob): string {
  if (job.type === "create-patient") return `ثبت بیمار: ${job.body.name}`;
  if (job.type === "book-visit") return `ویزیت ${job.body.patient_name} · ${job.body.scheduled_date}`;
  if (job.type === "book-surgery") return `عمل ${job.body.patient_name} · ${job.body.scheduled_date}`;
  if (job.type === "note") return "یادداشت داخلی پرونده";
  if (job.type === "exam") return "معاینه پرونده";
  return `وضعیت نوبت ← ${job.status}`;
}

export function AccountScreen({
  session,
  onSiteSaved,
  onLoggedOut,
  onFlush,
}: {
  session: Session;
  onSiteSaved: (session: Session) => void;
  onLoggedOut: () => void;
  onFlush: () => void;
}) {
  const [site, setSite] = useState(session.site);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [name, setName] = useState(session.user.name || "");
  const [mobile, setMobile] = useState(session.user.mobile || "");
  const [password, setPassword] = useState("");
  const [password2, setPassword2] = useState("");
  const [profileSaved, setProfileSaved] = useState(false);
  const items = useOutbox();

  return (
    <div className="page narrow">
      <header className="page-head">
        <div>
          <h1>حساب و اتصال</h1>
          <p className="lede">این دستگاه بعد از ورود، داده را محلی نگه می‌دارد و به همین هاست همگام می‌شود.</p>
        </div>
      </header>
      <div className="card">
        <strong>{session.user.name || "کاربر"}</strong>
        <p className="meta">
          {[session.user.role_label, session.user.national_code, session.user.mobile].filter(Boolean).join("  ·  ")}
        </p>
        <p className="hint">آخرین همگام موفق: {formatClock(lastSyncAt())}</p>
      </div>
      <div className="panel form-stack" style={{ marginTop: 16 }}>
        <h2>اطلاعات حساب</h2>
        <Field label="نام و نام خانوادگی" value={name} onChange={setName} />
        <Field label="موبایل" value={mobile} onChange={setMobile} />
        <Field label="رمز عبور جدید" value={password} onChange={setPassword} />
        <Field label="تکرار رمز" value={password2} onChange={setPassword2} />
        <PrimaryButton
          onClick={() => {
            setError(null);
            setProfileSaved(false);
            if (password && password !== password2) {
              setError("تکرار رمز مطابقت ندارد.");
              return;
            }
            void updateProfile(session, {
              name: name.trim(),
              mobile: mobile.trim() || undefined,
              password: password || undefined,
              password_confirmation: password2 || undefined,
            }).then((result) => {
              if (result.user) {
                const next = { ...session, user: { ...session.user, ...result.user } };
                saveSession(next);
                onSiteSaved(next);
              }
              setPassword("");
              setPassword2("");
              setProfileSaved(true);
            }).catch((err) => setError(err instanceof Error ? err.message : "حساب ذخیره نشد."));
          }}
        >
          {profileSaved ? "حساب ذخیره شد" : "ذخیره حساب"}
        </PrimaryButton>
      </div>
      {items.length > 0 ? (
        <div className="stack">
          <h2>صف ارسال</h2>
          {items.map((item) => (
            <div className="card" key={item.id}>
              <strong>{jobLabel(item.job)}</strong>
              <p className="meta">{item.status === "pending" || item.status === "sending" ? "در انتظار ارسال" : item.error || item.status}</p>
              <div className="action-row tight">
                {item.status === "failed" ? (
                  <button type="button" className="chip-btn" onClick={() => { retryOutbox(item.id); onFlush(); }}>
                    تلاش دوباره
                  </button>
                ) : null}
                {item.status === "rejected" || item.status === "failed" ? (
                  <button type="button" className="chip-btn" onClick={() => dismissOutbox(item.id)}>
                    بستن
                  </button>
                ) : null}
              </div>
            </div>
          ))}
          <GhostButton onClick={onFlush}>ارسال صف الان</GhostButton>
        </div>
      ) : null}
      <Field label="آدرس هاست سایت" value={site} onChange={(value) => { setSite(value); setSaved(false); }} />
      <ErrorBanner message={error} />
      <PrimaryButton
        onClick={() => {
          try {
            const next = { ...session, site: normalizeSite(site) };
            saveSession(next);
            onSiteSaved(next);
            setSaved(true);
            setError(null);
          } catch {
            setError("ذخیره آدرس انجام نشد.");
          }
        }}
      >
        {saved ? "ذخیره شد" : "ذخیره آدرس"}
      </PrimaryButton>
      <GhostButton
        onClick={() => {
          void logout(session).finally(onLoggedOut);
        }}
      >
        خروج از حساب
      </GhostButton>
    </div>
  );
}
