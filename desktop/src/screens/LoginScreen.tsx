import { FormEvent, useState } from "react";
import { Field, PrimaryButton, ErrorBanner } from "../components/ui";
import { ThemeToggle } from "../components/ThemeToggle";
import { login } from "../lib/api";
import { ApiError } from "../lib/api";
import { DEFAULT_SITE, saveSession } from "../lib/session";
import type { Session } from "../lib/types";

export function LoginScreen({ onLoggedIn }: { onLoggedIn: (session: Session) => void }) {
  const [site, setSite] = useState(DEFAULT_SITE);
  const [national, setNational] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const session = await login(site, national, password);
      saveSession(session);
      onLoggedIn(session);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "ورود انجام نشد.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="guest-shell">
      <ThemeToggle className="guest-theme" />
      <div className="guest-stage fade-up">
        <div className="guest-brand">
          <div className="guest-brand__icon float-soft">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" />
              <circle cx="12" cy="11" r="1.6" fill="currentColor" stroke="none" />
            </svg>
          </div>
          <h1 className="guest-brand__title">آرشیو بیمار</h1>
          <p className="guest-brand__sub">سامانه پرونده الکترونیک کلینیک</p>
        </div>
        <div className="guest-card">
          <form className="auth-form" onSubmit={submit}>
            <div className="auth-head">
              <h2 className="auth-head__title">ورود به سامانه</h2>
              <p className="auth-head__desc">کد ملی و رمز عبور خود را وارد کنید.</p>
            </div>
            <ErrorBanner message={error} />
            <Field label="کد ملی" value={national} onChange={setNational} autoComplete="username" />
            <Field label="رمز عبور" value={password} onChange={setPassword} type="password" autoComplete="current-password" />
            <label className="form-check">
              <input type="checkbox" checked={remember} onChange={(event) => setRemember(event.target.checked)} />
              <span>مرا به خاطر بسپار</span>
            </label>
            <PrimaryButton type="submit" loading={loading}>
              ورود به سامانه
            </PrimaryButton>
            <details className="host-details">
              <summary>آدرس هاست</summary>
              <Field label="آدرس سایت" value={site} onChange={setSite} />
            </details>
          </form>
        </div>
        <p className="guest-foot">دسترسی امن · مناسب قلم نوری · طراحی برای تیم درمان</p>
      </div>
    </div>
  );
}
