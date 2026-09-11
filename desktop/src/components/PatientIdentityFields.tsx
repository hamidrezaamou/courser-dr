import { useEffect, useState } from "react";
import { FieldBlock, TextInput } from "./BookingForm";
import { loadPatient, loadPatients } from "../lib/api";
import { digitsOnly } from "../lib/session";
import type { Session } from "../lib/types";

type LookupStatus = "idle" | "looking" | "found" | "new" | "error";

export function PatientIdentityFields({
  session,
  patientId,
  variant = "lookup",
  name,
  national,
  mobile,
  mobileSecondary = "",
  age,
  noNationalCode = false,
  onName,
  onNational,
  onMobile,
  onMobileSecondary,
  onAge,
  onNoNationalCode,
  onResolvedId,
}: {
  session: Session;
  patientId?: number;
  variant?: "lookup" | "known" | "edit";
  name: string;
  national: string;
  mobile: string;
  mobileSecondary?: string;
  age: string;
  noNationalCode?: boolean;
  onName: (value: string) => void;
  onNational: (value: string) => void;
  onMobile: (value: string) => void;
  onMobileSecondary?: (value: string) => void;
  onAge: (value: string) => void;
  onNoNationalCode?: (value: boolean) => void;
  onResolvedId: (id: number) => void;
}) {
  const [status, setStatus] = useState<LookupStatus>(patientId || variant !== "lookup" ? "found" : "idle");
  const lookup = variant === "lookup" && !patientId;
  const locked = lookup && status === "found";
  const detailsOpen = !lookup || noNationalCode || status === "found" || status === "new" || status === "error" || !!name || !!mobile;

  useEffect(() => {
    if (!patientId) return;
    let alive = true;
    (async () => {
      try {
        const file = await loadPatient(session, patientId);
        if (!alive) return;
        const patient = file.data.patient;
        if (!patient) return;
        onName(patient.name || "");
        onNational(patient.national_code || "");
        onMobile(patient.mobile || "");
        onMobileSecondary?.(patient.mobile_secondary || "");
        onAge(patient.age || "");
        onNoNationalCode?.(!patient.national_code);
        onResolvedId(patient.id);
        setStatus("found");
      } catch {
        if (alive) setStatus("error");
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, patientId]);

  useEffect(() => {
    if (!lookup || noNationalCode) return;
    const code = digitsOnly(national);
    if (code.length !== 10) {
      setStatus("idle");
      onResolvedId(0);
      return;
    }
    let alive = true;
    setStatus("looking");
    const timer = window.setTimeout(async () => {
      try {
        const result = await loadPatients(session, code);
        if (!alive) return;
        const match = (result.data.patients || []).find((item) => digitsOnly(item.national_code || "") === code);
        if (match) {
          onName(match.name || "");
          onMobile(match.mobile || "");
          onMobileSecondary?.(match.mobile_secondary || "");
          onAge(match.age || "");
          onResolvedId(match.id);
          setStatus("found");
        } else {
          onResolvedId(0);
          setStatus("new");
        }
      } catch {
        if (!alive) return;
        onResolvedId(0);
        setStatus("error");
      }
    }, 280);
    return () => {
      alive = false;
      window.clearTimeout(timer);
    };
  }, [session, national, lookup, noNationalCode]);

  function toggleNoCode(checked: boolean) {
    onNoNationalCode?.(checked);
    if (checked) {
      onNational("");
      onResolvedId(0);
      setStatus("new");
    } else {
      setStatus("idle");
    }
  }

  const title = variant === "known" ? "مشخصات بیمار" : "شناسه بیمار";
  const hint = variant === "known"
    ? "این فیلدها از پرونده بیمار آمده‌اند."
    : "ابتدا کد ملی را وارد کنید. اگر پرونده باشد مشخصات از پرونده پر می‌شود و قابل تغییر از این فرم نیست.";

  return (
    <div className="space-y-4">
      {variant !== "edit" ? (
        <div>
          <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>{title}</h3>
          <p className="mt-1 text-xs" style={{ color: "var(--muted)" }}>{hint}</p>
        </div>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-2">
        {lookup ? (
          <>
            <FieldBlock label="کد ملی">
              <TextInput
                id="national_code"
                dir="ltr"
                inputMode="numeric"
                maxLength={10}
                required={!noNationalCode}
                disabled={noNationalCode}
                value={national}
                onChange={(value) => onNational(digitsOnly(value).slice(0, 10))}
              />
              <label className="mt-2 flex items-center gap-2 text-xs" style={{ color: "var(--muted)" }}>
                <input type="checkbox" checked={noNationalCode} onChange={(event) => toggleNoCode(event.target.checked)} />
                فاقد کد ملی
              </label>
              {status === "looking" ? <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>در حال جستجوی پرونده…</p> : null}
              {status === "found" ? <p className="mt-1 text-[11px] font-bold" style={{ color: "var(--brand-dark)" }}>پرونده پیدا شد — مشخصات از پرونده پر شد.</p> : null}
              {status === "new" ? <p className="mt-1 text-[11px] font-bold" style={{ color: "var(--warn, #b45309)" }}>پرونده‌ای با این کد ملی نیست — مشخصات جدید را وارد کنید.</p> : null}
              {status === "error" ? <p className="mt-1 text-[11px] text-rose-600">خطا در جستجو. دوباره تلاش کنید.</p> : null}
            </FieldBlock>
            <FieldBlock label="نام و نام خانوادگی">
              <TextInput value={name} required={detailsOpen} readOnly={locked} onChange={onName} />
              {locked ? <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>نام از پرونده است و از اینجا عوض نمی‌شود.</p> : null}
              {!locked && !detailsOpen ? <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>بعد از ورود کد ملی، اگر پرونده نبود نام فعال می‌شود.</p> : null}
            </FieldBlock>
          </>
        ) : (
          <>
            <FieldBlock label="نام و نام خانوادگی">
              <TextInput value={name} required onChange={onName} />
            </FieldBlock>
            <FieldBlock label="کد ملی">
              <TextInput
                dir="ltr"
                inputMode="numeric"
                maxLength={10}
                required={!noNationalCode}
                disabled={noNationalCode}
                value={national}
                onChange={(value) => onNational(digitsOnly(value).slice(0, 10))}
              />
              <label className="mt-2 flex items-center gap-2 text-xs" style={{ color: "var(--muted)" }}>
                <input type="checkbox" checked={noNationalCode} onChange={(event) => toggleNoCode(event.target.checked)} />
                فاقد کد ملی
              </label>
            </FieldBlock>
          </>
        )}
      </div>

      {detailsOpen ? (
        <div className="grid gap-4 sm:grid-cols-2">
          <FieldBlock label="شماره تماس">
            <TextInput dir="ltr" inputMode="tel" maxLength={11} required value={mobile} readOnly={locked} onChange={(value) => onMobile(digitsOnly(value).slice(0, 11))} />
          </FieldBlock>
          <FieldBlock label="سن">
            <TextInput value={age} readOnly={locked} onChange={onAge} />
          </FieldBlock>
          {onMobileSecondary ? (
            <FieldBlock label="شماره تماس دوم (اختیاری)" className="sm:col-span-2">
              <TextInput dir="ltr" inputMode="tel" maxLength={11} value={mobileSecondary} readOnly={locked} onChange={(value) => onMobileSecondary(digitsOnly(value).slice(0, 11))} />
            </FieldBlock>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}
