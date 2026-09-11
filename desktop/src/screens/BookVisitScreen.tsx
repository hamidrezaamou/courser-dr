import { useEffect, useState } from "react";
import { PatientIdentityFields } from "../components/PatientIdentityFields";
import { BookingSlotGrid, DateSelect, FieldBlock, SelectInput, TextInput } from "../components/BookingForm";
import { PageChrome } from "../components/PageChrome";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, loadAppointment, loadSlots, loadVisitCalendar, updateVisit } from "../lib/api";
import { dateOptionsFromDays } from "../lib/bookingDates";
import { enqueueVisit, flushOutbox } from "../lib/outbox";
import { digitsOnly } from "../lib/session";
import type { Session, SlotDto } from "../lib/types";

const VISIT_TYPES = ["معاینه عمومی", "پیگیری", "اورژانس", "مشاوره", "سایر"];

export function BookVisitScreen({
  session,
  patientId,
  bookingId,
  onBack,
  onDone,
}: {
  session: Session;
  patientId?: number;
  bookingId?: number;
  onBack: () => void;
  onDone: (patientId?: number) => void;
}) {
  const [resolvedId, setResolvedId] = useState(patientId || 0);
  const [name, setName] = useState("");
  const [national, setNational] = useState("");
  const [mobile, setMobile] = useState("");
  const [mobileSecondary, setMobileSecondary] = useState("");
  const [age, setAge] = useState("");
  const [noNationalCode, setNoNationalCode] = useState(false);
  const [days, setDays] = useState<ReturnType<typeof dateOptionsFromDays>>([]);
  const [date, setDate] = useState("");
  const [slots, setSlots] = useState<SlotDto[]>([]);
  const [slot, setSlot] = useState("");
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [slotsMessage, setSlotsMessage] = useState<string | null>(null);
  const [hideBooked, setHideBooked] = useState(false);
  const [visitType, setVisitType] = useState("");
  const [reason, setReason] = useState("");
  const [notes, setNotes] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [calendarMessage, setCalendarMessage] = useState<string | null>(null);

  const identityVariant = bookingId ? "edit" : patientId ? "known" : "lookup";
  const slotLabel = slots.find((item) => item.value === slot)?.label || slot || "";

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const calendar = await loadVisitCalendar(session);
        if (!alive) return;
        setCalendarMessage(calendar.data.message || null);
        let keep = "";
        if (bookingId) {
          const loaded = await loadAppointment(session, bookingId);
          const item = loaded.appointment;
          if (item) {
            setName(item.patient_name || "");
            setNational(item.national_code || "");
            setNoNationalCode(!item.national_code);
            setMobile(item.mobile || "");
            setMobileSecondary(item.mobile_secondary || "");
            setAge(item.age || "");
            setVisitType(item.visit_type || item.title || "");
            setReason(item.reason || item.subtitle || "");
            setNotes(item.notes || "");
            keep = item.scheduled_date_jalali || "";
            setDate(keep);
            setSlot(item.scheduled_time || "");
            if (item.patient_id) setResolvedId(item.patient_id);
          }
        }
        setDays(dateOptionsFromDays(calendar.data.days || {}, { keep }));
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "تقویم ویزیت بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, bookingId]);

  useEffect(() => {
    if (!date) {
      setSlots([]);
      setSlot("");
      setSlotsMessage(null);
      return;
    }
    let alive = true;
    setSlotsLoading(true);
    (async () => {
      try {
        const result = await loadSlots(session, date, "visit", { excludeId: bookingId || null });
        if (!alive) return;
        const list = result.data.slots || [];
        setSlots(list);
        setSlotsMessage(result.data.message || null);
        setSlot((current) => {
          if (current && list.some((item) => item.value === current)) return current;
          return list.find((item) => item.bookable)?.value || current || "";
        });
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "ساعت‌های این روز در کش نیست.");
      } finally {
        if (alive) setSlotsLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, date, bookingId]);

  const canSave = Boolean(
    name.trim() &&
      digitsOnly(mobile).length === 11 &&
      (noNationalCode || digitsOnly(national).length === 10) &&
      date &&
      slot,
  );

  function save() {
    if (!date || !slot) return;
    setSaving(true);
    setError(null);
    const body = {
      patient_name: name.trim(),
      national_code: noNationalCode ? "" : digitsOnly(national),
      no_national_code: noNationalCode,
      mobile: digitsOnly(mobile),
      mobile_secondary: digitsOnly(mobileSecondary) || undefined,
      age: age.trim() || undefined,
      visit_type: visitType,
      reason: reason.trim() || undefined,
      notes: notes.trim() || undefined,
      scheduled_date: date,
      scheduled_time: slot,
    };
    if (bookingId) {
      void updateVisit(session, bookingId, body)
        .then(() => onDone(resolvedId || undefined))
        .catch((err) => setError(err instanceof ApiError ? err.message : "نوبت ذخیره نشد."))
        .finally(() => setSaving(false));
      return;
    }
    enqueueVisit(resolvedId, body, slotLabel);
    void flushOutbox(session).finally(() => {
      setSaving(false);
      onDone(resolvedId || undefined);
    });
  }

  return (
    <PageChrome
      width="3xl"
      header={
        <div className="flex items-center justify-between gap-3">
          <h2 className="page-title">{bookingId ? "ویرایش ویزیت" : "ثبت ویزیت"}</h2>
          <button type="button" className="btn-ghost !text-xs !px-2.5 !py-1.5" onClick={onBack}>
            {patientId || bookingId ? "پرونده" : "داشبورد"}
          </button>
        </div>
      }
    >
      <ErrorBanner message={error} />
      {loading ? <LoadingBox /> : null}
      {!loading ? (
        <div className="panel fade-up space-y-6 p-5 sm:p-8">
          <PatientIdentityFields
            session={session}
            patientId={identityVariant === "known" ? patientId : undefined}
            variant={identityVariant}
            name={name}
            national={national}
            mobile={mobile}
            mobileSecondary={mobileSecondary}
            age={age}
            noNationalCode={noNationalCode}
            onName={setName}
            onNational={setNational}
            onMobile={setMobile}
            onMobileSecondary={setMobileSecondary}
            onAge={setAge}
            onNoNationalCode={setNoNationalCode}
            onResolvedId={setResolvedId}
          />

          <div className="border-t pt-5" style={{ borderColor: "var(--line)" }}>
            <h3 className="mb-4 text-sm font-bold" style={{ color: "var(--ink)" }}>زمان‌بندی نوبت</h3>
            <div className="mb-4">
              <FieldBlock label="نوع ویزیت">
                <SelectInput value={visitType} onChange={setVisitType}>
                  <option value="">انتخاب کنید</option>
                  {VISIT_TYPES.map((item) => <option key={item} value={item}>{item}</option>)}
                </SelectInput>
              </FieldBlock>
            </div>

            <div className="booking-picker">
              <div className="grid gap-4 sm:grid-cols-2">
                <FieldBlock label="تاریخ نوبت">
                  <DateSelect value={date} onChange={setDate} options={days} emptyLabel={calendarMessage || "روز بازی نیست"} />
                </FieldBlock>
                <FieldBlock label="ساعت انتخاب‌شده">
                  <input className="field-input mt-1" readOnly required value={slotLabel} placeholder="از گرید زیر انتخاب کنید" />
                </FieldBlock>
              </div>
              {date ? (
                <div className="booking-time-section mt-4">
                  <div className="booking-slot-info">
                    تایم‌های آزاد برای تاریخ <strong>{date}</strong>
                  </div>
                  <BookingSlotGrid
                    slots={slots}
                    selected={slot}
                    loading={slotsLoading}
                    empty={slotsMessage}
                    hideBooked={hideBooked}
                    onSelect={setSlot}
                  />
                  <label className="mt-3 flex items-center gap-2 text-xs" style={{ color: "var(--muted)" }}>
                    <input type="checkbox" checked={hideBooked} onChange={(event) => setHideBooked(event.target.checked)} />
                    مخفی کردن تایم‌های پر شده
                  </label>
                </div>
              ) : null}
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2">
              <FieldBlock label="علت مراجعه" className="sm:col-span-2">
                <TextInput value={reason} onChange={setReason} />
              </FieldBlock>
              <FieldBlock label="توضیحات" className="sm:col-span-2">
                <textarea className="field-input mt-1" rows={3} value={notes} onChange={(event) => setNotes(event.target.value)} />
              </FieldBlock>
            </div>
          </div>

          <div className="flex flex-wrap gap-3">
            <button type="button" className="btn-primary" disabled={!canSave || saving} onClick={save}>
              {saving ? "لطفاً صبر کنید…" : bookingId ? "ذخیره تغییرات" : "ثبت ویزیت"}
            </button>
            <button type="button" className="btn-secondary" onClick={onBack}>انصراف</button>
          </div>
        </div>
      ) : null}
    </PageChrome>
  );
}
