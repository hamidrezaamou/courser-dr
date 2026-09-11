import { FormEvent, useState } from "react";
import { ErrorBanner, Field, GhostButton, PrimaryButton } from "../components/ui";
import { enqueueCreatePatient, flushOutbox, mappedId } from "../lib/outbox";
import { digitsOnly } from "../lib/session";
import type { Session } from "../lib/types";

export function CreatePatientScreen({
  session,
  onBack,
  onCreated,
}: {
  session: Session;
  onBack: () => void;
  onCreated: (id: number) => void;
}) {
  const [name, setName] = useState("");
  const [national, setNational] = useState("");
  const [mobile, setMobile] = useState("");
  const [age, setAge] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const localId = enqueueCreatePatient({
        name: name.trim(),
        national_code: digitsOnly(national),
        mobile: digitsOnly(mobile),
        age: age.trim() || undefined,
      });
      await flushOutbox(session);
      onCreated(mappedId(localId));
    } catch {
      setError("ثبت انجام نشد.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="page narrow">
      <header className="page-head">
        <h1 className="page-title">ثبت بیمار جدید</h1>
        <GhostButton onClick={onBack}>داشبورد</GhostButton>
      </header>
      <form className="panel form-stack" onSubmit={submit}>
        <p className="notice-mint">رمز ورود اولیه بیمار برابر با <strong>شماره موبایل</strong> او خواهد بود.</p>
        <ErrorBanner message={error} />
        <Field label="نام و نام خانوادگی" value={name} onChange={setName} />
        <Field label="کد ملی" value={national} onChange={(value) => setNational(digitsOnly(value).slice(0, 10))} />
        <Field label="شماره موبایل" value={mobile} onChange={(value) => setMobile(digitsOnly(value).slice(0, 11))} />
        <Field label="سن (اختیاری)" value={age} onChange={setAge} />
        <div className="head-actions">
          <PrimaryButton type="submit" loading={loading}>
            ثبت بیمار
          </PrimaryButton>
          <GhostButton onClick={onBack}>بازگشت</GhostButton>
        </div>
      </form>
    </div>
  );
}
