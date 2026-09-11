import { FormEvent, useEffect, useState } from "react";
import { ErrorBanner, Field, GhostButton, LoadingBox, PrimaryButton } from "../components/ui";
import { ApiError, loadPatient, updatePatient } from "../lib/api";
import { digitsOnly } from "../lib/session";
import type { Session } from "../lib/types";

export function EditPatientScreen({
  session,
  patientId,
  onBack,
  onSaved,
}: {
  session: Session;
  patientId: number;
  onBack: () => void;
  onSaved: () => void;
}) {
  const [name, setName] = useState("");
  const [national, setNational] = useState("");
  const [mobile, setMobile] = useState("");
  const [mobile2, setMobile2] = useState("");
  const [age, setAge] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const file = await loadPatient(session, patientId);
        if (!alive) return;
        const patient = file.data.patient;
        setName(patient?.name || "");
        setNational(patient?.national_code || "");
        setMobile(patient?.mobile || "");
        setMobile2(patient?.mobile_secondary || "");
        setAge(patient?.age || "");
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "پرونده بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, patientId]);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await updatePatient(session, patientId, {
        name: name.trim(),
        national_code: digitsOnly(national),
        mobile: digitsOnly(mobile),
        mobile_secondary: digitsOnly(mobile2) || undefined,
        age: age.trim() || undefined,
      });
      onSaved();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "ذخیره نشد.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="page narrow">
      <header className="page-head">
        <h1 className="page-title">ویرایش بیمار</h1>
        <GhostButton onClick={onBack}>بازگشت به پرونده</GhostButton>
      </header>
      {loading ? <LoadingBox /> : (
        <form className="panel form-stack" onSubmit={submit}>
          <ErrorBanner message={error} />
          <Field label="نام و نام خانوادگی" value={name} onChange={setName} />
          <div className="form-grid">
            <Field label="کد ملی" value={national} onChange={(value) => setNational(digitsOnly(value).slice(0, 10))} />
            <Field label="شماره موبایل" value={mobile} onChange={(value) => setMobile(digitsOnly(value).slice(0, 11))} />
            <Field label="شماره موبایل دوم (اختیاری)" value={mobile2} onChange={(value) => setMobile2(digitsOnly(value).slice(0, 11))} />
            <Field label="سن (اختیاری)" value={age} onChange={setAge} />
          </div>
          <label className="field">
            <span>عکس پرونده</span>
            <input type="file" accept="image/*" onChange={(event) => {
              const file = event.target.files?.[0];
              if (!file) return;
              void import("../lib/ops").then(({ uploadPatientPhoto }) => uploadPatientPhoto(session, patientId, file)).catch((err) => setError(err instanceof ApiError ? err.message : "عکس ذخیره نشد."));
            }} />
          </label>
          <div className="head-actions">
            <GhostButton onClick={onBack}>انصراف</GhostButton>
            <PrimaryButton type="submit" loading={saving}>ذخیره تغییرات</PrimaryButton>
          </div>
        </form>
      )}
    </div>
  );
}
