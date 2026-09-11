import { useEffect, useRef, useState, useMemo } from "react";
import { PatientIdentityFields } from "../components/PatientIdentityFields";
import { BookingSlotGrid, DateSelect, FieldBlock, SelectInput, TextInput } from "../components/BookingForm";
import { PageChrome } from "../components/PageChrome";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError, checkCooldown, loadHospitals, loadSlots, loadSurgery, loadSurgeryOptions, updateSurgery } from "../lib/api";
import { dateOptionsFromDays } from "../lib/bookingDates";
import { enqueueSurgery, flushOutbox, mappedId } from "../lib/outbox";
import { uploadDocuments, waitingStatus } from "../lib/ops";
import { digitsOnly } from "../lib/session";
import type { BookingPrefill, HospitalDto, Session, SlotBookingDto, SlotDto, SurgeryTypeDto } from "../lib/types";

const EYES = [
  { value: "OD", label: "راست (OD)" },
  { value: "OS", label: "چپ (OS)" },
  { value: "OU", label: "هر دو (OU)" },
];

function PhotoPreview({ files, onClear }: { files: File[]; onClear: () => void }) {
  const urls = useMemo(() => files.map((file) => ({ name: file.name, url: URL.createObjectURL(file) })), [files]);
  useEffect(() => () => urls.forEach((item) => URL.revokeObjectURL(item.url)), [urls]);
  return (
    <div className="doc-upload-preview mt-3">
      {urls.map((item) => (
        <img key={item.url} src={item.url} alt={item.name} className="doc-upload-preview__img" />
      ))}
      <div className="doc-upload-preview__meta">
        <strong>{files.length} عکس آماده</strong>
        <button type="button" className="doc-upload-preview__clear" onClick={onClear}>حذف</button>
      </div>
    </div>
  );
}

export function BookSurgeryScreen({
  session,
  patientId,
  bookingId,
  prefill,
  onBack,
  onDone,
}: {
  session: Session;
  patientId?: number;
  bookingId?: number;
  prefill?: BookingPrefill;
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
  const [hospitals, setHospitals] = useState<HospitalDto[]>([]);
  const [hospitalId, setHospitalId] = useState("");
  const [types, setTypes] = useState<SurgeryTypeDto[]>([]);
  const [typeId, setTypeId] = useState("");
  const [subtypeId, setSubtypeId] = useState("");
  const [loadingCatalog, setLoadingCatalog] = useState(false);
  const [days, setDays] = useState<ReturnType<typeof dateOptionsFromDays>>([]);
  const [date, setDate] = useState("");
  const [slots, setSlots] = useState<SlotDto[]>([]);
  const [slot, setSlot] = useState("");
  const [slotsLoading, setSlotsLoading] = useState(false);
  const [slotsMessage, setSlotsMessage] = useState<string | null>(null);
  const [showBooked, setShowBooked] = useState(false);
  const [eye, setEye] = useState("");
  const [surgeon, setSurgeon] = useState(session.user.role === "doctor" ? (session.user.name || "") : "");
  const [notes, setNotes] = useState("");
  const [emergency, setEmergency] = useState(false);
  const [exception, setException] = useState(false);
  const [exceptionDraft, setExceptionDraft] = useState("");
  const [showExceptionTime, setShowExceptionTime] = useState(false);
  const [photos, setPhotos] = useState<File[]>([]);
  const [docs, setDocs] = useState<File[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [cooldown, setCooldown] = useState<{ message: string; confirm: string; previous?: { date?: string | null; time?: string | null; hospital?: string | null; type?: string | null; subtype?: string | null; eye?: string | null; status?: string | null; when?: string | null; scope?: string | null } | null } | null>(null);
  const [bookedModal, setBookedModal] = useState<SlotDto | null>(null);
  const pendingEdit = useRef<{ typeId: string; subtypeId: string; date: string; slot: string } | null>(null);
  const prefillDate = useRef(prefill?.date || "");
  const cameraRef = useRef<HTMLInputElement>(null);
  const galleryRef = useRef<HTMLInputElement>(null);

  const hospitalNum = Number(hospitalId) || 0;
  const typeNum = Number(typeId) || 0;
  const subtypeNum = Number(subtypeId) || null;
  const type = types.find((item) => String(item.id) === typeId) || null;
  const subtypes = type?.subtypes || [];
  const identityVariant = bookingId ? "edit" : patientId ? "known" : "lookup";
  const slotLabel = slots.find((item) => item.value === slot)?.label || slot || "";
  const slotMode = slots.some((item) => (item.label || "").includes("نوبت")) ? "queue" : "clock";
  const hasFullDays = days.some((item) => item.full);
  const subtypeReady = !typeId || type?.has_general !== false || !!subtypeId;

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      try {
        const hospitalList = await loadHospitals(session);
        if (!alive) return;
        setHospitals(hospitalList.data);
        if (bookingId) {
          const loaded = await loadSurgery(session, bookingId);
          const item = loaded.surgery;
          if (item) {
            setName(item.patient_name || "");
            setNational(item.national_code || "");
            setNoNationalCode(!item.national_code);
            setMobile(item.mobile || "");
            setMobileSecondary(item.mobile_secondary || "");
            setAge(item.age || "");
            setEye(item.eye_side || "");
            setSurgeon(item.surgeon_name || "");
            setNotes(item.notes || "");
            setEmergency(!!item.is_emergency);
            setException(!!item.is_exception);
            pendingEdit.current = {
              typeId: item.surgery_type_id ? String(item.surgery_type_id) : "",
              subtypeId: item.surgery_subtype_id ? String(item.surgery_subtype_id) : "",
              date: item.scheduled_date_jalali || "",
              slot: item.scheduled_time || "",
            };
            if (item.patient_id) setResolvedId(item.patient_id);
            if (item.hospital_id) setHospitalId(String(item.hospital_id));
          }
        } else if (prefill) {
          if (!patientId) {
            setName(prefill.name || "");
            setNational(prefill.national_code || "");
            setMobile(prefill.mobile || "");
          }
          setNotes(prefill.notes || "");
          if (prefill.date) prefillDate.current = prefill.date;
        }
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "فهرست بیمارستان بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, bookingId, patientId, prefill]);

  useEffect(() => {
    if (!hospitalNum) {
      setTypes([]);
      setTypeId("");
      return;
    }
    let alive = true;
    setLoadingCatalog(true);
    (async () => {
      try {
        const catalog = await loadSurgeryOptions(session, hospitalNum);
        if (!alive) return;
        setTypes(catalog.data.types || []);
        const pending = pendingEdit.current;
        if (pending?.typeId) {
          setTypeId(pending.typeId);
          setSubtypeId(pending.subtypeId);
        } else {
          setTypeId("");
          setSubtypeId("");
        }
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "انواع عمل در کش نیست.");
      } finally {
        if (alive) setLoadingCatalog(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, hospitalNum]);

  useEffect(() => {
    if (!hospitalNum || !typeNum || !subtypeReady) {
      setDays([]);
      setDate("");
      return;
    }
    let alive = true;
    (async () => {
      try {
        const calendar = await loadSurgeryOptions(session, hospitalNum, typeNum, subtypeNum);
        if (!alive) return;
        const pending = pendingEdit.current;
        const keep = pending?.date || prefillDate.current || date;
        const options = dateOptionsFromDays(calendar.data.days || {}, { showBooked, keep });
        setDays(options);
        setDate((current) => pending?.date || prefillDate.current || (options.some((item) => item.value === current) ? current : ""));
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "روزهای عمل در کش نیست.");
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, hospitalNum, typeNum, subtypeNum, subtypeReady, showBooked]);

  useEffect(() => {
    if (!date || !hospitalNum || !typeNum) {
      setSlots([]);
      setSlot("");
      return;
    }
    let alive = true;
    setSlotsLoading(true);
    (async () => {
      try {
        const result = await loadSlots(session, date, "surgery", {
          hospitalId: hospitalNum,
          typeId: typeNum,
          subtypeId: subtypeNum,
          excludeId: bookingId || null,
        });
        if (!alive) return;
        const keep = pendingEdit.current?.slot || slot;
        const list = result.data.slots || [];
        setSlots(list);
        setSlotsMessage(result.data.message || null);
        setSlot((current) => {
          const preferred = pendingEdit.current?.slot || current || keep;
          if (preferred && list.some((item) => item.value === preferred)) return preferred;
          return list.find((item) => item.bookable)?.value || preferred || "";
        });
        if (pendingEdit.current) pendingEdit.current = null;
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "نوبت‌های این روز در کش نیست.");
      } finally {
        if (alive) setSlotsLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, date, hospitalNum, typeNum, subtypeNum, bookingId]);

  const hospitalName = hospitals.find((item) => item.id === hospitalNum)?.name;

  useEffect(() => {
    if (!date || !typeNum || !eye) {
      setCooldown(null);
      return;
    }
    let alive = true;
    void checkCooldown(session, {
      patientId: resolvedId || null,
      date,
      surgeryTypeId: typeNum,
      surgerySubtypeId: subtypeNum,
      eyeSide: eye,
      excludeId: bookingId || null,
      nationalCode: digitsOnly(national),
    }).then((result) => {
      if (!alive) return;
      if (result.conflict) {
        setCooldown({
          message: result.message || "فاصله زمانی با عمل قبلی رعایت نشده است.",
          confirm: result.confirm_message || result.message || "",
          previous: result.previous,
        });
      } else {
        setCooldown(null);
      }
    }).catch(() => {
      if (alive) setCooldown(null);
    });
    return () => {
      alive = false;
    };
  }, [session, resolvedId, date, typeNum, subtypeNum, eye, bookingId, national]);

  const canSave = Boolean(
    name.trim() &&
      digitsOnly(mobile).length === 11 &&
      (noNationalCode || digitsOnly(national).length === 10) &&
      hospitalNum &&
      type &&
      eye &&
      date &&
      slot,
  );

  function addPhotos(list: FileList | null) {
    if (!list?.length) return;
    setPhotos((current) => [...current, ...Array.from(list)]);
  }

  function confirmException() {
    const value = exceptionDraft.trim();
    if (!value) return;
    setSlot(value);
    setException(true);
    setShowExceptionTime(false);
    setSlots((current) => {
      if (current.some((item) => item.value === value)) return current;
      return [...current, { value, label: value, bookable: true, exception: true }];
    });
  }

  async function save() {
    if (!hospitalNum || !type || !date || !slot) return;
    if (cooldown && !window.confirm(cooldown.confirm)) return;
    setSaving(true);
    setError(null);
    const body = {
      patient_name: name.trim(),
      national_code: noNationalCode ? "" : digitsOnly(national),
      no_national_code: noNationalCode,
      mobile: digitsOnly(mobile),
      mobile_secondary: digitsOnly(mobileSecondary) || undefined,
      age: age.trim() || undefined,
      hospital_id: hospitalNum,
      surgery_type: type.name || "",
      surgery_type_id: typeNum || null,
      surgery_subtype_id: subtypeNum,
      eye_side: eye,
      surgeon_name: surgeon.trim() || undefined,
      notes: notes.trim() || undefined,
      is_emergency: emergency,
      is_exception: exception,
      scheduled_date: date,
      scheduled_time: slot,
      hospital_name: hospitalName,
    };
    try {
      if (bookingId) {
        await updateSurgery(session, bookingId, body);
      } else {
        enqueueSurgery(resolvedId, body, slotLabel);
        await flushOutbox(session);
        if (prefill?.waiting_id) await waitingStatus(session, prefill.waiting_id, "converted");
      }
      const pid = mappedId(resolvedId) || resolvedId;
      if (pid > 0 && (photos.length || docs.length)) {
        const pack = new DataTransfer();
        photos.concat(docs).forEach((file) => pack.items.add(file));
        if (pack.files.length) await uploadDocuments(session, pid, pack.files, "عکس عمل");
      }
      onDone(pid || undefined);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "نوبت عمل ذخیره نشد.");
    } finally {
      setSaving(false);
    }
  }

  const booked = bookedModal?.booking as SlotBookingDto | undefined;

  return (
    <PageChrome
      width="3xl"
      padded
      gap=""
      header={
        <div className="flex items-center justify-between gap-3">
          <h2 className="page-title">{bookingId ? "ویرایش عمل" : "ثبت عمل"}</h2>
          <button type="button" className="btn-ghost !text-xs !px-2.5 !py-1.5" onClick={onBack}>
            {patientId || bookingId ? "پرونده" : "داشبورد"}
          </button>
        </div>
      }
    >
      <ErrorBanner message={error} />
      {loading ? <LoadingBox /> : null}
      {!loading ? (
        <form className="panel fade-up space-y-5 p-4 sm:p-6" onSubmit={(event) => { event.preventDefault(); void save(); }}>
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

          <div className="border-t pt-5 space-y-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>زمان‌بندی عمل</h3>

            <FieldBlock label="۱) بیمارستان">
              <SelectInput value={hospitalId} required onChange={(value) => { setHospitalId(value); setTypeId(""); setSubtypeId(""); }}>
                <option value="">انتخاب بیمارستان...</option>
                {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
              </SelectInput>
            </FieldBlock>

            {hospitalId ? (
              <div className="grid gap-3 sm:grid-cols-2">
                <FieldBlock label="۲) نوع عمل">
                  <SelectInput value={typeId} required disabled={loadingCatalog || !types.length} onChange={(value) => { setTypeId(value); setSubtypeId(""); }}>
                    <option value="">انتخاب...</option>
                    {types.map((item) => <option key={item.id} value={item.id || ""}>{item.name}</option>)}
                  </SelectInput>
                  {loadingCatalog ? <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>بارگذاری انواع عمل...</p> : null}
                  {!loadingCatalog && hospitalId && !types.length ? <p className="mt-1 text-[11px] text-rose-600">برای این بیمارستان برنامه‌ای ثبت نشده.</p> : null}
                </FieldBlock>
                <FieldBlock label="۳) زیرگروه">
                  <SelectInput value={subtypeId} disabled={!typeId} onChange={setSubtypeId}>
                    {type?.has_general !== false ? <option value="">عمومی</option> : <option value="">انتخاب...</option>}
                    {subtypes.map((item) => <option key={item.id} value={item.id || ""}>{item.name}</option>)}
                  </SelectInput>
                </FieldBlock>
              </div>
            ) : null}

            {hospitalId && typeId && subtypeReady ? (
              <div>
                <label className="mb-3 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-xs font-bold" style={{ borderColor: "var(--line)", color: "var(--ink)", background: "color-mix(in srgb, var(--brand) 6%, var(--panel))" }}>
                  <input type="checkbox" className="mt-0.5" checked={showBooked} onChange={(event) => setShowBooked(event.target.checked)} />
                  <span>
                    نمایش روزها و نوبت‌های پر شده
                    <span className="mt-0.5 block font-medium" style={{ color: "var(--muted)" }}>با فعال کردن این گزینه می‌توانید روزهای تکمیل‌شده را انتخاب کنید، جزئیات نوبت‌های پر را ببینید و برای همان روز نوبت استثنا تعریف کنید.</span>
                  </span>
                </label>
                <FieldBlock label="۴) تاریخ عمل">
                  <DateSelect value={date} onChange={setDate} options={days} emptyLabel="تاریخی برای این محدوده باقی نمانده است." />
                </FieldBlock>
                {!days.length ? <p className="mt-1 text-xs text-rose-600">تاریخی برای این محدوده باقی نمانده است.</p> : null}
                {!showBooked && hasFullDays ? <p className="mt-1 text-xs" style={{ color: "var(--muted)" }}>روزهای تکمیل‌شده در لیست هستند ولی غیرفعال‌اند؛ برای انتخاب آن‌ها تیک بالا را بزنید.</p> : null}
              </div>
            ) : null}

            {date && hospitalId && typeId ? (
              <div>
                <label className="block text-sm font-bold" style={{ color: "var(--ink)" }}>{slotMode === "queue" ? "۵) نوبت آزاد" : "۵) ساعت آزاد"}</label>
                <BookingSlotGrid
                  slots={slots}
                  selected={slot}
                  loading={slotsLoading}
                  empty={slotsMessage}
                  hideBooked={!showBooked}
                  onSelect={(value, _booked, isException) => {
                    setSlot(value);
                    setException(!!isException);
                  }}
                  onBooked={setBookedModal}
                />
                <div className="exception-btn-wrapper mt-3">
                  <button type="button" className="btn-exception" onClick={() => setShowExceptionTime(true)}>➕ نوبت استثنا</button>
                  <span className="exception-hint">یک نوبت اضافی فراتر از ظرفیت برنامه ایجاد می‌کند</span>
                </div>
                {showExceptionTime ? (
                  <div className="mt-3">
                    <FieldBlock label="ساعت نوبت استثنا">
                      <div className="mt-1 flex gap-2">
                        <input type="time" className="field-input" dir="ltr" value={exceptionDraft} onChange={(event) => setExceptionDraft(event.target.value)} />
                        <button type="button" className="btn-secondary shrink-0" onClick={confirmException}>تأیید</button>
                      </div>
                    </FieldBlock>
                  </div>
                ) : null}
                <label className="mt-2 flex items-center gap-2 text-xs font-bold" style={{ color: "var(--warn)" }}>
                  <input type="checkbox" checked={emergency} onChange={(event) => setEmergency(event.target.checked)} /> اورژانس
                </label>
              </div>
            ) : null}

            <div className="grid gap-4 sm:grid-cols-2">
              <FieldBlock label="چشم *">
                <SelectInput value={eye} required onChange={setEye}>
                  <option value="">انتخاب کنید</option>
                  {EYES.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
                </SelectInput>
              </FieldBlock>
              <FieldBlock label="نام جراح">
                <TextInput value={surgeon} onChange={setSurgeon} />
              </FieldBlock>
            </div>

            {cooldown ? (
              <div className="cooldown-warn mt-3">
                <p className="cooldown-warn__title">{cooldown.previous?.scope === "type" ? "محدودیت کل نوع عمل" : "محدودیت فاصله زمانی"}</p>
                <p className="cooldown-warn__msg">{cooldown.message}</p>
                {cooldown.previous ? (
                  <dl className="cooldown-warn__grid">
                    <div><dt>نوبت تداخلی</dt><dd>{cooldown.previous.date} · {cooldown.previous.time}{cooldown.previous.when ? ` (${cooldown.previous.when})` : ""}</dd></div>
                    <div><dt>مرکز</dt><dd>{cooldown.previous.hospital}</dd></div>
                    <div><dt>عمل</dt><dd>{cooldown.previous.type}{cooldown.previous.subtype ? ` · ${cooldown.previous.subtype}` : ""}</dd></div>
                    <div><dt>چشم / وضعیت</dt><dd>{cooldown.previous.eye} · {cooldown.previous.status}</dd></div>
                  </dl>
                ) : null}
                <p className="cooldown-warn__hint">ثبت مسدود نمی‌شود؛ اگر ادامه دهید باید این هشدار را تأیید کنید.</p>
              </div>
            ) : null}

            <FieldBlock label="توضیحات / آمادگی پیش از عمل">
              <textarea className="field-input mt-1" rows={3} value={notes} onChange={(event) => setNotes(event.target.value)} />
            </FieldBlock>
          </div>

          {!bookingId ? (
            <div className="border-t pt-5 space-y-4" style={{ borderColor: "var(--line)" }}>
              <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>پیوست‌ها (مستقیم در پرونده بیمار)</h3>
              <div>
                <label className="form-label">عکس‌های عمل / مدارک تصویری</label>
                <input ref={cameraRef} type="file" accept="image/*" capture="environment" multiple className="sr-only" onChange={(event) => addPhotos(event.target.files)} />
                <input ref={galleryRef} type="file" accept="image/*" multiple className="sr-only" onChange={(event) => addPhotos(event.target.files)} />
                <div className="doc-upload-sources mt-2">
                  <button type="button" className="doc-upload-source" onClick={() => cameraRef.current?.click()}>
                    <span className="doc-upload-source__title">دوربین</span>
                    <span className="doc-upload-source__hint">عکس بگیرید</span>
                  </button>
                  <button type="button" className="doc-upload-source" onClick={() => galleryRef.current?.click()}>
                    <span className="doc-upload-source__title">گالری</span>
                    <span className="doc-upload-source__hint">از گوشی یا فایل</span>
                  </button>
                </div>
                {photos.length ? (
                  <PhotoPreview files={photos} onClear={() => setPhotos([])} />
                ) : null}
                <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>چند عکس همزمان — پس از ثبت در گالری پرونده بیمار دیده می‌شوند.</p>
              </div>
              <FieldBlock label="مدارک دیگر (تصویر یا PDF)">
                <input className="field-input mt-1" type="file" accept="image/*,.pdf,application/pdf" multiple onChange={(event) => setDocs(event.target.files ? Array.from(event.target.files) : [])} />
              </FieldBlock>
            </div>
          ) : null}

          <div className="flex flex-wrap gap-3">
            <button type="submit" className="btn-primary btn-accent-warm" disabled={!canSave || saving}>
              {saving ? "لطفاً صبر کنید…" : bookingId ? "ذخیره تغییرات" : "ثبت عمل و پرونده"}
            </button>
            <button type="button" className="btn-secondary" onClick={onBack}>انصراف</button>
          </div>
        </form>
      ) : null}

      {bookedModal ? (
        <div className="booked-slot-modal" onClick={() => setBookedModal(null)}>
          <div className="booked-slot-modal__backdrop" />
          <div className="booked-slot-modal__panel panel" role="dialog" onClick={(event) => event.stopPropagation()}>
            <div className="flex items-start justify-between gap-3 border-b pb-3" style={{ borderColor: "var(--line)" }}>
              <div>
                <h4 className="text-sm font-extrabold" style={{ color: "var(--ink)" }}>جزئیات نوبت پر شده</h4>
                <p className="mt-0.5 text-xs" style={{ color: "var(--muted)" }}>{bookedModal.label || bookedModal.value}</p>
              </div>
              <button type="button" className="booked-slot-modal__close" onClick={() => setBookedModal(null)} aria-label="بستن">×</button>
            </div>
            {booked ? (
              <dl className="booked-slot-modal__grid mt-4">
                <div><dt>نام بیمار</dt><dd>{booked.patient_name || "—"}</dd></div>
                <div><dt>موبایل</dt><dd dir="ltr">{booked.mobile || "—"}</dd></div>
                {booked.national_code ? <div><dt>کد ملی</dt><dd dir="ltr">{booked.national_code}</dd></div> : null}
                <div><dt>نوع عمل</dt><dd>{booked.surgery_type || "—"}</dd></div>
                {booked.subtype_name ? <div><dt>زیرگروه</dt><dd>{booked.subtype_name}</dd></div> : null}
                {booked.eye_side ? <div><dt>چشم</dt><dd>{booked.eye_side}</dd></div> : null}
                {booked.surgeon_name ? <div><dt>جراح</dt><dd>{booked.surgeon_name}</dd></div> : null}
                <div><dt>وضعیت</dt><dd>{booked.status_label || booked.status || "—"}</dd></div>
                {booked.is_exception ? <div><dt>نوع</dt><dd>نوبت استثنا</dd></div> : null}
                {booked.is_emergency ? <div><dt>اولویت</dt><dd className="text-rose-700">اورژانس</dd></div> : null}
              </dl>
            ) : <p className="mt-4 text-sm" style={{ color: "var(--muted)" }}>جزئیات این نوبت در دسترس نیست.</p>}
            <div className="mt-4 flex flex-wrap gap-2">
              <button type="button" className="btn-ghost text-xs" onClick={() => setBookedModal(null)}>بستن</button>
            </div>
          </div>
        </div>
      ) : null}
    </PageChrome>
  );
}
