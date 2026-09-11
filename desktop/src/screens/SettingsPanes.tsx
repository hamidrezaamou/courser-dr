import { FormEvent, useEffect, useMemo, useState } from "react";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { ApiError } from "../lib/api";
import {
  destroyDrug,
  destroyHospital,
  destroySurgeryType,
  destroyTime,
  loadCatalog,
  loadTimes,
  storeDrug,
  storeHospital,
  storeSubtype,
  storeSurgeryType,
  storeTime,
  updateDrug,
  updateHospital,
  updateTimeSms,
  type DrugDto,
  type TimeDayDto,
} from "../lib/ops";
import { ChecklistSettingsPane } from "./ClinicToolsPages";
import type { HospitalDto, Session, SurgeryTypeDto } from "../lib/types";

const MONTHS = ["", "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
const DOSAGE_FORMS = ["قرص", "کپسول", "قطره", "پماد", "آمپول", "شربت", "اسپری"];
const TIME_TEMPLATES: Array<{ label: string; value: string }> = [
  { label: "صبح", value: "08:00\n09:00\n10:00\n11:00\n12:00" },
  { label: "بعدازظهر", value: "14:00\n15:00\n16:00\n17:00\n18:00" },
  { label: "۳۰ دقیقه‌ای", value: "08:00\n08:30\n09:00\n09:30\n10:00\n10:30" },
  { label: "ساعتی", value: "09:00\n10:00\n11:00\n13:00\n14:00\n15:00" },
];

function monthOf(item: TimeDayDto) {
  return Number(item.month) || Number(String(item.date_key || "").split(/[/-]/)[1]) || 0;
}

function dayOf(item: TimeDayDto) {
  return Number(item.day) || Number(String(item.date_key || "").split(/[/-]/)[2]) || 0;
}

function groupByMonth(items: TimeDayDto[]) {
  const map = new Map<number, TimeDayDto[]>();
  for (const item of items) {
    const month = monthOf(item);
    const list = map.get(month) || [];
    list.push(item);
    map.set(month, list);
  }
  return [...map.entries()].sort((a, b) => a[0] - b[0]);
}

function currentJalaliMonth() {
  try {
    return Number(new Intl.DateTimeFormat("fa-IR-u-ca-persian", { month: "numeric" }).format(new Date()).replace(/[^\d]/g, "")) || 0;
  } catch {
    return 0;
  }
}

export function TimesPane({ session }: { session: Session }) {
  const [kind, setKind] = useState<"visit" | "surgery">("visit");
  const [items, setItems] = useState<TimeDayDto[]>([]);
  const [hospitals, setHospitals] = useState<HospitalDto[]>([]);
  const [types, setTypes] = useState<SurgeryTypeDto[]>([]);
  const [hospitalId, setHospitalId] = useState("");
  const [typeId, setTypeId] = useState("");
  const [showAddHospital, setShowAddHospital] = useState(false);
  const [showAddType, setShowAddType] = useState(false);
  const [newHospitalName, setNewHospitalName] = useState("");
  const [newTypeName, setNewTypeName] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [dateKey, setDateKey] = useState("");
  const [slotMode, setSlotMode] = useState<"time" | "queue">("queue");
  const [slots, setSlots] = useState("10");
  const [timesText, setTimesText] = useState("");
  const [smsText, setSmsText] = useState("");
  const [defaultSms, setDefaultSms] = useState("");
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [bulkSms, setBulkSms] = useState("");
  const [smsDrafts, setSmsDrafts] = useState<Record<number, string>>({});
  const [openMonths, setOpenMonths] = useState<Record<number, boolean>>({});

  const hospitalName = hospitals.find((item) => String(item.id) === hospitalId)?.name || "";
  const typeName = types.find((item) => String(item.id) === typeId)?.name || "";

  async function refreshCatalog() {
    const data = await loadCatalog(session);
    setHospitals(data.hospitals || []);
    setTypes((data.surgery_types || []).map((item) => ({
      id: String(item.id),
      name: item.name,
      subtypes: item.subtypes?.map((sub) => ({ id: String(sub.id), name: sub.name })),
    })));
  }

  async function refresh() {
    const extra = kind === "surgery"
      ? { hospitalId: Number(hospitalId) || null, typeId: Number(typeId) || null }
      : undefined;
    const data = await loadTimes(session, kind, extra);
    const rows = data.items || [];
    setItems(rows);
    setDefaultSms(data.default_sms || "");
    setSmsDrafts(Object.fromEntries(rows.map((row) => [row.id, row.sms_text || ""])));
    if (!smsText) setSmsText(data.default_sms || "");
  }

  useEffect(() => {
    void refreshCatalog().catch(() => undefined);
  }, [session]);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        await refresh();
      } catch (err) {
        if (!alive) return;
        setError(err instanceof ApiError ? err.message : "تایم‌ها بارگذاری نشد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session, kind, hospitalId, typeId]);

  const grouped = useMemo(() => groupByMonth(items), [items]);
  const jalaliMonth = currentJalaliMonth();

  function toggleMonth(month: number) {
    setOpenMonths((current) => ({ ...current, [month]: !(current[month] ?? month === jalaliMonth) }));
  }

  function toggleId(id: number) {
    setSelectedIds((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
  }

  async function onAddDay(event: FormEvent) {
    event.preventDefault();
    setError(null);
    if (kind === "surgery" && !hospitalId) {
      setError("برای تایم عمل، بیمارستان را انتخاب کنید.");
      return;
    }
    try {
      await storeTime(session, {
        kind,
        date_key: dateKey,
        total_slots: Number(slots) || 10,
        hospital_id: kind === "surgery" ? Number(hospitalId) || undefined : undefined,
        surgery_type_id: kind === "surgery" ? Number(typeId) || undefined : undefined,
        slot_mode: slotMode,
        times: slotMode === "time" ? timesText : undefined,
        sms_text: smsText || undefined,
      });
      setNotice("روز ثبت شد.");
      setDateKey("");
      await refresh();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "روز ثبت نشد.");
    }
  }

  return (
    <div className="space-y-5">
      <ErrorBanner message={error} />
      {notice ? <p className="text-xs font-bold" style={{ color: "var(--brand-dark)" }}>{notice}</p> : null}

      <div className="kind-switch panel !p-1.5">
        <div className="kind-switch__track" dir="ltr">
          <span className={kind === "surgery" ? "kind-switch__thumb is-surgery" : "kind-switch__thumb is-visit"} style={{ transform: kind === "surgery" ? "translate3d(100%,0,0)" : "translate3d(0,0,0)" }} />
          <button type="button" className={kind === "visit" ? "kind-switch__btn is-active" : "kind-switch__btn"} onClick={() => setKind("visit")}>تایم‌های ویزیت</button>
          <button type="button" className={kind === "surgery" ? "kind-switch__btn is-active" : "kind-switch__btn"} onClick={() => setKind("surgery")}>تایم‌های عمل</button>
        </div>
      </div>

      {kind === "surgery" ? (
        <div className="panel p-4 sm:p-5">
          <p className="mb-3 text-xs font-bold" style={{ color: "var(--muted)" }}>محدوده برنامه عمل</p>
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <div className="mb-1.5 flex items-center justify-between gap-2">
                <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>بیمارستان</label>
                <button type="button" className="scope-plus" onClick={() => setShowAddHospital((value) => !value)}>+</button>
              </div>
              {showAddHospital ? (
                <div className="mb-2 flex gap-2">
                  <input className="field-input !py-2 text-sm" placeholder="نام جدید" value={newHospitalName} onChange={(event) => setNewHospitalName(event.target.value)} />
                  <button type="button" className="btn-secondary !py-2 !px-3 shrink-0" onClick={() => void storeHospital(session, { name: newHospitalName }).then(() => { setNewHospitalName(""); setShowAddHospital(false); return refreshCatalog(); })}>ثبت</button>
                </div>
              ) : null}
              <select className="field-input" value={hospitalId} onChange={(event) => setHospitalId(event.target.value)}>
                <option value="">همه</option>
                {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
              </select>
            </div>
            <div>
              <div className="mb-1.5 flex items-center justify-between gap-2">
                <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نوع عمل</label>
                <button type="button" className="scope-plus" onClick={() => setShowAddType((value) => !value)}>+</button>
              </div>
              {showAddType ? (
                <div className="mb-2 flex gap-2">
                  <input className="field-input !py-2 text-sm" placeholder="مثلاً آب مروارید" value={newTypeName} onChange={(event) => setNewTypeName(event.target.value)} />
                  <button type="button" className="btn-secondary !py-2 !px-3 shrink-0" onClick={() => void storeSurgeryType(session, newTypeName).then(() => { setNewTypeName(""); setShowAddType(false); return refreshCatalog(); })}>ثبت</button>
                </div>
              ) : null}
              <select className="field-input" value={typeId} onChange={(event) => setTypeId(event.target.value)}>
                <option value="">همه انواع</option>
                {types.map((item) => <option key={item.id} value={item.id || ""}>{item.name}</option>)}
              </select>
            </div>
          </div>
        </div>
      ) : null}

      <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section className="panel overflow-hidden p-0">
          <div className="flex items-center justify-between border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>
              روزهای تنظیم‌شده · {kind === "surgery" ? "عمل" : "ویزیت"}
              {kind === "surgery" && hospitalName ? <span className="font-medium" style={{ color: "var(--muted)" }}> · {hospitalName}{typeName ? ` / ${typeName}` : ""}</span> : null}
            </h3>
            <span className={`tone rounded-full px-3 py-1 text-xs font-bold ${kind === "surgery" ? "tone--surgery" : "tone--visit"}`}>{items.length} روز</span>
          </div>
          <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3" style={{ borderColor: "var(--line)", background: "color-mix(in srgb, var(--panel-soft) 70%, transparent)" }}>
            <button type="button" className="text-[11px] font-bold" style={{ color: "var(--brand)" }} onClick={() => setSelectedIds(items.map((item) => item.id))}>انتخاب همهٔ روزهای نمایش‌داده‌شده</button>
            {selectedIds.length ? (
              <div className="flex flex-wrap gap-2">
                <span className="text-[11px] font-bold" style={{ color: "var(--brand-dark)" }}>{selectedIds.length} روز انتخاب شده</span>
                <button type="button" className="text-[11px] font-bold text-slate-600" onClick={() => { setSelectedIds([]); setBulkSms(""); }}>لغو انتخاب</button>
                <button type="button" className="text-[11px] font-bold text-red-700" onClick={() => {
                  if (!confirm("حذف روزهای انتخاب‌شده؟")) return;
                  void Promise.all(selectedIds.map((id) => destroyTime(session, id))).then(() => { setSelectedIds([]); return refresh(); });
                }}>حذف انتخاب‌شده‌ها</button>
              </div>
            ) : null}
          </div>
          <div className="border-b px-5 py-3" style={{ borderColor: "var(--line)", background: "color-mix(in srgb, var(--brand) 6%, var(--panel))" }}>
            <p className="mb-2 text-xs font-extrabold" style={{ color: "var(--ink)" }}>پیامک مشترک روزهای انتخاب‌شده</p>
            <p className="mb-2 text-[11px]" style={{ color: "var(--muted)" }}>
              {selectedIds.length ? `پیامک مشترک برای ${selectedIds.length} روز انتخاب‌شده` : "روزهای ایجادشده را تیک بزنید، سپس یک متن برای همه آن‌ها ذخیره کنید."}
            </p>
            <textarea rows={3} className="field-input text-sm" maxLength={1000} disabled={!selectedIds.length} value={bulkSms} onChange={(event) => setBulkSms(event.target.value)} placeholder="متن پیامک را بنویسید و ذخیره کنید…" />
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <button type="button" className="btn-primary !py-2 !px-3 !text-xs" disabled={!selectedIds.length} onClick={() => void Promise.all(selectedIds.map((id) => updateTimeSms(session, id, bulkSms))).then(() => { setNotice("متن پیامک ذخیره شد."); return refresh(); })}>ذخیره پیامک برای روزهای انتخاب‌شده</button>
              <span className="text-[11px]" style={{ color: "var(--muted)" }}>متغیرها: {"{name} {date} {time} {hospital} {slotNumber} {surgeryType}"}</span>
            </div>
          </div>
          <div className="p-5">
            {loading ? <LoadingBox /> : null}
            {!loading && items.length === 0 ? <div className="pp-empty">{kind === "surgery" && hospitals.length === 0 ? "ابتدا یک بیمارستان از منوی «بیمارستان‌ها» اضافه کنید." : "هنوز روزی برای این محدوده ثبت نشده است."}</div> : null}
            {grouped.map(([month, days]) => {
              const open = openMonths[month] ?? month === jalaliMonth;
              return (
                <div key={month} className={open ? "times-month is-open" : "times-month"}>
                  <button type="button" className="times-month__summary" onClick={() => toggleMonth(month)}>
                    <span className="times-month__title">
                      <svg className="times-month__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d="M6 9l6 6 6-6" /></svg>
                      <span>{MONTHS[month] || month}</span>
                    </span>
                    <span className="times-month__count">{days.length} روز</span>
                  </button>
                  {open ? (
                    <div className="times-month__body">
                      <div className="flex flex-wrap gap-3">
                        {days.map((day) => {
                          const total = day.total_slots || day.times_count || 0;
                          const selected = selectedIds.includes(day.id);
                          return (
                            <div
                              key={day.id}
                              className={`min-w-[7.5rem] flex-1 cursor-pointer rounded-2xl border p-3 text-center sm:min-w-[96px] sm:flex-none border-emerald-200 bg-emerald-50/70 ${selected ? "ring-2 ring-rose-400" : ""}`}
                              onClick={(event) => {
                                if ((event.target as HTMLElement).closest("input, button, textarea, details, summary")) return;
                                toggleId(day.id);
                              }}
                            >
                              <label className="mb-1 flex items-center justify-center gap-1 text-[10px] font-bold text-slate-600">
                                <input type="checkbox" checked={selected} onChange={() => toggleId(day.id)} />
                                انتخاب
                              </label>
                              <div className="text-[11px]" style={{ color: "var(--muted)" }}>{day.weekday || ""}</div>
                              <div className="text-lg font-extrabold" style={{ color: "var(--ink)" }}>{dayOf(day) || day.date_key}</div>
                              {day.surgery_type_name ? (
                                <div className="mt-1 text-[10px] leading-tight" style={{ color: "var(--muted)" }}>
                                  {day.surgery_type_name}{day.surgery_subtype_name ? ` · ${day.surgery_subtype_name}` : ""}
                                </div>
                              ) : null}
                              <div className="mt-1 text-[10px] font-semibold text-emerald-700">{total} نوبت</div>
                              <div className="mt-1 text-[10px] font-bold" style={{ color: "var(--brand)" }}>{day.times_count || total} تایم</div>
                              <details className="times-sms-edit mt-1 text-right" onClick={(event) => event.stopPropagation()}>
                                <summary className="times-sms-edit__toggle">✉️ پیامک</summary>
                                <div className="times-sms-edit__form">
                                  <textarea rows={3} className="field-input !text-[11px] !py-1.5" maxLength={1000} value={smsDrafts[day.id] ?? ""} onChange={(event) => setSmsDrafts((current) => ({ ...current, [day.id]: event.target.value }))} />
                                  <button type="button" className="times-sms-edit__save" onClick={() => void updateTimeSms(session, day.id, smsDrafts[day.id] || "").then(() => setNotice("متن پیامک این روز ذخیره شد."))}>ذخیره پیامک</button>
                                </div>
                              </details>
                              <button type="button" className="mt-2 text-[11px] font-bold text-red-600" onClick={() => { if (confirm("حذف این روز؟")) void destroyTime(session, day.id).then(refresh); }}>حذف</button>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  ) : null}
                </div>
              );
            })}
          </div>
        </section>

        <section className="panel p-5 sm:p-6">
          <h3 className="mb-1 text-sm font-bold" style={{ color: "var(--ink)" }}>افزودن روز جدید</h3>
          <p className="mb-4 text-xs" style={{ color: "var(--muted)" }}>
            در حال ثبت برای <strong style={{ color: kind === "surgery" ? "#c2410c" : "var(--brand-dark)" }}>{kind === "surgery" ? "عمل" : "ویزیت"}</strong>
            {kind === "surgery" ? <> · <strong style={{ color: "#c2410c" }}>{hospitalName || "—"}</strong> · <strong>{typeName || "نوع را انتخاب کنید"}</strong></> : null}
          </p>
          <form className="space-y-4" onSubmit={onAddDay}>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>تاریخ شمسی</label>
              <input className="field-input mt-1" placeholder="۱۴۰۵/۰۶/۲۰" value={dateKey} onChange={(event) => setDateKey(event.target.value)} required />
              <p className="mt-1 text-[11px]" style={{ color: "var(--muted)" }}>تاریخ را به صورت سال/ماه/روز وارد کنید.</p>
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نوع نوبت‌دهی</label>
              <div className="mt-2 grid grid-cols-2 gap-2">
                <button type="button" className="btn-secondary !justify-center !py-2" style={slotMode === "time" ? { background: "var(--brand-dark)", color: "#fff", borderColor: "transparent" } : undefined} onClick={() => setSlotMode("time")}>ساعت مشخص</button>
                <button type="button" className="btn-secondary !justify-center !py-2" style={slotMode === "queue" ? { background: "var(--brand-dark)", color: "#fff", borderColor: "transparent" } : undefined} onClick={() => setSlotMode("queue")}>نوبت شماره‌ای</button>
              </div>
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>ظرفیت</label>
              <input type="number" min={1} max={100} className="field-input mt-1" value={slots} onChange={(event) => setSlots(event.target.value)} />
            </div>
            {slotMode === "queue" ? (
              <div>
                <p className="text-[11px]" style={{ color: "var(--muted)" }}>به‌جای ساعت، نوبت ۱ تا {slots || "۱۰"} ذخیره می‌شود.</p>
                <div className="mt-2 grid grid-cols-2 gap-2">
                  {[5, 10, 15, 20].map((count) => (
                    <button key={count} type="button" className="btn-secondary !justify-center !px-2 !py-2 !text-xs" onClick={() => setSlots(String(count))}>نوبت ۱–{count}</button>
                  ))}
                </div>
              </div>
            ) : (
              <div>
                <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>ساعت‌های حضور (هر خط یک ساعت)</label>
                <textarea rows={6} dir="ltr" className="field-input mt-1 font-mono" value={timesText} onChange={(event) => setTimesText(event.target.value)} placeholder={"08:00\n08:30\n09:00"} />
                <div className="mt-2 grid grid-cols-2 gap-2">
                  {TIME_TEMPLATES.map((item) => (
                    <button key={item.label} type="button" className="btn-secondary !justify-center !px-2 !py-2 !text-xs" onClick={() => setTimesText(item.value)}>{item.label}</button>
                  ))}
                </div>
              </div>
            )}
            <div className="rounded-2xl border p-3" style={{ borderColor: "var(--line)", background: "var(--panel-soft)" }}>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>✉️ متن پیامک آماده</label>
              <textarea rows={3} className="field-input mt-1.5 text-sm" maxLength={1000} value={smsText || defaultSms} onChange={(event) => setSmsText(event.target.value)} placeholder="متن پیامک این روز…" />
              <p className="mt-1.5 text-[11px] leading-relaxed" style={{ color: "var(--muted)" }}>متغیرها: {"{name} {date} {time} {hospital}"}</p>
            </div>
            <button type="submit" className="btn-primary w-full !py-3">ثبت روز</button>
          </form>
        </section>
      </div>
    </div>
  );
}

export function HospitalsPane({ session }: { session: Session }) {
  const [items, setItems] = useState<HospitalDto[]>([]);
  const [name, setName] = useState("");
  const [address, setAddress] = useState("");
  const [phone, setPhone] = useState("");
  const [edits, setEdits] = useState<Record<number, { name: string; address: string; phone: string }>>({});
  const [error, setError] = useState<string | null>(null);

  async function refresh() {
    const catalog = await loadCatalog(session);
    const rows = catalog.hospitals || [];
    setItems(rows);
    setEdits(Object.fromEntries(rows.map((item) => [item.id, { name: item.name || "", address: item.address || "", phone: item.phone || "" }])));
  }

  useEffect(() => {
    void refresh().catch((err) => setError(err instanceof ApiError ? err.message : "بیمارستان‌ها نیامد."));
  }, [session]);

  return (
    <div className="space-y-5">
      <ErrorBanner message={error} />
      <div className="panel overflow-hidden p-0 hospitals-hero" style={{ background: "linear-gradient(135deg, var(--brand-dark), var(--brand))", color: "#fff" }}>
        <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:gap-4 sm:px-7 sm:py-6">
          <div className="flex items-center gap-3 sm:gap-4">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 sm:h-12 sm:w-12">
              <svg className="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 21h16.5M4.5 3h15v18h-15V3zM9 7.5h1.5M9 11.25h1.5M9 15h1.5M13.5 7.5H15M13.5 11.25H15M13.5 15H15" />
              </svg>
            </div>
            <div>
              <h3 className="text-base font-extrabold sm:text-lg">مراکز ثبت‌شده</h3>
              <p className="mt-0.5 hidden text-sm text-white/85 sm:block">نام، آدرس و تلفن در ثبت نوبت عمل و قالب‌های پرینت استفاده می‌شود.</p>
            </div>
          </div>
          <div className="rounded-2xl bg-white/15 px-4 py-2 text-center ring-1 ring-white/20 sm:px-5 sm:py-3">
            <div className="text-xl font-extrabold leading-none sm:text-2xl">{items.length}</div>
            <div className="mt-1 text-[10px] text-white/90 sm:text-xs">مرکز</div>
          </div>
        </div>
      </div>

      <div className="grid gap-5 lg:grid-cols-1 xl:grid-cols-[380px_minmax(0,1fr)]">
        <section className="panel p-5 sm:p-6">
          <h3 className="mb-4 flex items-center gap-2 text-sm font-bold" style={{ color: "var(--ink)" }}>
            <svg className="h-4 w-4" style={{ color: "var(--brand)" }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            افزودن بیمارستان جدید
          </h3>
          <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            void storeHospital(session, { name, address, phone }).then(() => { setName(""); setAddress(""); setPhone(""); return refresh(); }).catch((err) => setError(err instanceof ApiError ? err.message : "ثبت نشد."));
          }}>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نام بیمارستان / مرکز جراحی</label>
              <input className="field-input mt-1 block w-full" value={name} onChange={(event) => setName(event.target.value)} required placeholder="مثال: بیمارستان دی" />
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>آدرس (برای پرینت)</label>
              <input className="field-input mt-1 block w-full" value={address} onChange={(event) => setAddress(event.target.value)} placeholder="مثال: مشهد، بلوار …" />
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>تلفن (برای پرینت)</label>
              <input className="field-input mt-1 block w-full ltr-data" dir="ltr" value={phone} onChange={(event) => setPhone(event.target.value)} placeholder="051-12345678" />
            </div>
            <button type="submit" className="btn-primary w-full !py-3">ذخیره در سیستم</button>
          </form>
        </section>

        <section className="panel overflow-hidden p-0">
          <div className="flex items-center justify-between border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>لیست مراکز طرف قرارداد</h3>
            <span className="rounded-full px-3 py-1 text-xs font-bold" style={{ background: "var(--brand-soft)", color: "var(--brand-dark)" }}>{items.length} مرکز</span>
          </div>
          {items.length === 0 ? (
            <div className="pp-empty">هنوز هیچ بیمارستانی ثبت نشده است.<br />از فرم کنار صفحه اولین مرکز را اضافه کنید.</div>
          ) : (
            <div className="divide-y" style={{ borderColor: "var(--line)" }}>
              {items.map((item) => {
                const edit = edits[item.id] || { name: item.name || "", address: item.address || "", phone: item.phone || "" };
                return (
                  <details key={item.id} className="group px-5 py-3">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3">
                      <div className="flex min-w-0 items-center gap-3">
                        <span className="inline-flex min-w-[2rem] shrink-0 items-center justify-center rounded-lg px-2 py-1 text-xs font-bold" style={{ background: "var(--panel-soft)", color: "var(--muted)" }}>{item.id}</span>
                        <div className="min-w-0">
                          <strong className="block truncate" style={{ color: "var(--ink)" }}>{item.name}</strong>
                          <span className="mt-0.5 block truncate text-xs" style={{ color: "var(--muted)" }}>
                            {item.address || item.phone ? `${item.address || "—"}` : "آدرس و تلفن ثبت نشده"}
                            {item.phone ? <> · <span dir="ltr">{item.phone}</span></> : null}
                          </span>
                        </div>
                      </div>
                    </summary>
                    <form className="mt-3 space-y-2 border-t pt-3" style={{ borderColor: "var(--line)" }} onSubmit={(event) => {
                      event.preventDefault();
                      void updateHospital(session, item.id, edit).then(refresh);
                    }}>
                      <input className="field-input !py-2 text-sm" value={edit.name} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, name: event.target.value } }))} required placeholder="نام بیمارستان" />
                      <input className="field-input !py-2 text-sm" value={edit.address} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, address: event.target.value } }))} placeholder="آدرس" />
                      <input className="field-input !py-2 text-sm ltr-data" dir="ltr" value={edit.phone} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, phone: event.target.value } }))} placeholder="تلفن" />
                      <button type="submit" className="btn-secondary !py-2 !text-xs">ذخیره</button>
                    </form>
                    <button type="button" className="mt-2 text-xs font-bold text-red-600" onClick={() => { if (confirm("آیا از حذف این بیمارستان مطمئن هستید؟")) void destroyHospital(session, item.id).then(refresh); }}>حذف</button>
                  </details>
                );
              })}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}

export function TypesPane({ session }: { session: Session }) {
  const [types, setTypes] = useState<Array<{ id: number; name?: string; is_active?: boolean; subtypes?: Array<{ id: number; name?: string }> }>>([]);
  const [name, setName] = useState("");
  const [subNames, setSubNames] = useState<Record<number, string>>({});

  async function refresh() {
    const catalog = await loadCatalog(session);
    setTypes(catalog.surgery_types || []);
  }
  useEffect(() => { void refresh(); }, [session]);

  return (
    <div className="space-y-5">
      <p className="text-sm" style={{ color: "var(--muted)" }}>
        برای ظرفیت مستقل چند نوع عمل، از بخش <strong>برنامه‌ها</strong> گروه برنامه نوبت بسازید.
      </p>
      <div className="grid gap-5 lg:grid-cols-1 xl:grid-cols-[340px_minmax(0,1fr)]">
        <section className="panel p-5">
          <h3 className="mb-4 text-sm font-bold" style={{ color: "var(--ink)" }}>افزودن نوع عمل</h3>
          <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            void storeSurgeryType(session, name).then(() => { setName(""); return refresh(); });
          }}>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نام نوع عمل</label>
              <input className="field-input mt-1 block w-full" value={name} onChange={(event) => setName(event.target.value)} required placeholder="مثال: آب مروارید" />
            </div>
            <button type="submit" className="btn-primary w-full !py-3">ذخیره نوع</button>
          </form>
        </section>
        <section className="panel overflow-hidden p-0">
          <div className="flex items-center justify-between border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
            <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>لیست انواع و زیرگروه‌ها</h3>
            <span className="rounded-full px-3 py-1 text-xs font-bold" style={{ background: "var(--brand-soft)", color: "var(--brand-dark)" }}>{types.length} نوع</span>
          </div>
          {types.length === 0 ? <div className="pp-empty">هنوز نوعی ثبت نشده.</div> : (
            <div className="divide-y" style={{ borderColor: "var(--line)" }}>
              {types.map((item) => (
                <div key={item.id} className="space-y-3 p-4">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <strong style={{ color: "var(--ink)" }}>{item.name}</strong>
                    <button type="button" className="touch-action w-full text-red-600 sm:w-auto" style={{ background: "color-mix(in srgb, #fee2e2 80%, transparent)" }} onClick={() => { if (confirm("حذف نوع و همه زیرگروه‌ها؟")) void destroySurgeryType(session, item.id).then(refresh); }}>حذف</button>
                  </div>
                  <div className="rounded-xl border p-3" style={{ borderColor: "var(--line)", background: "var(--panel-soft)" }}>
                    <p className="mb-2 text-xs font-bold" style={{ color: "var(--muted)" }}>زیرگروه‌ها</p>
                    <ul className="mb-3 space-y-2">
                      {(item.subtypes || []).length === 0 ? <li className="text-xs" style={{ color: "var(--muted)" }}>هنوز زیرگروهی نیست.</li> : item.subtypes?.map((sub) => (
                        <li key={sub.id} className="rounded-lg px-2 py-1.5 text-sm font-bold" style={{ background: "var(--panel)", color: "var(--ink)" }}>{sub.name}</li>
                      ))}
                    </ul>
                    <form className="flex gap-2" onSubmit={(event) => {
                      event.preventDefault();
                      const value = (subNames[item.id] || "").trim();
                      if (!value) return;
                      void storeSubtype(session, item.id, value).then(() => { setSubNames((current) => ({ ...current, [item.id]: "" })); return refresh(); });
                    }}>
                      <input className="field-input" placeholder="زیرگروه جدید + " value={subNames[item.id] || ""} onChange={(event) => setSubNames((current) => ({ ...current, [item.id]: event.target.value }))} />
                      <button type="submit" className="btn-secondary shrink-0 !px-3" title="افزودن">+</button>
                    </form>
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
      <section className="panel p-5">
        <h3 className="mb-3 text-sm font-bold" style={{ color: "var(--ink)" }}>قالب چک‌لیست عمل</h3>
        <ChecklistSettingsPane session={session} />
      </section>
    </div>
  );
}

export function DrugsPane({ session }: { session: Session }) {
  const empty = { name: "", generic_name: "", dosage_form: "", default_dosage: "", default_frequency: "", default_duration: "", default_instructions: "" };
  const [items, setItems] = useState<DrugDto[]>([]);
  const [form, setForm] = useState(empty);
  const [edits, setEdits] = useState<Record<number, DrugDto>>({});

  async function refresh() {
    const data = await loadCatalog(session);
    const rows = data.drugs || [];
    setItems(rows);
    setEdits(Object.fromEntries(rows.map((item) => [item.id, item])));
  }
  useEffect(() => { void refresh(); }, [session]);

  return (
    <div className="grid gap-5 lg:grid-cols-1 xl:grid-cols-[400px_minmax(0,1fr)]">
      <section className="panel p-5 sm:p-6">
        <h3 className="mb-4 text-sm font-bold" style={{ color: "var(--ink)" }}>افزودن دارو</h3>
        <form className="space-y-3" onSubmit={(event) => {
          event.preventDefault();
          void storeDrug(session, form).then(() => { setForm(empty); return refresh(); });
        }}>
          <div>
            <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نام دارو</label>
            <input className="field-input mt-1 block w-full" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required />
          </div>
          <div>
            <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>نام ژنریک (اختیاری)</label>
            <input className="field-input mt-1 block w-full" value={form.generic_name} onChange={(event) => setForm({ ...form, generic_name: event.target.value })} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>شکل دارویی</label>
              <select className="field-input mt-1" value={form.dosage_form} onChange={(event) => setForm({ ...form, dosage_form: event.target.value })}>
                <option value="">—</option>
                {DOSAGE_FORMS.map((item) => <option key={item} value={item}>{item}</option>)}
              </select>
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>دوز پیش‌فرض</label>
              <input className="field-input mt-1 block w-full" value={form.default_dosage} onChange={(event) => setForm({ ...form, default_dosage: event.target.value })} placeholder="مثلاً ۱ قطره" />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>دفعات مصرف</label>
              <input className="field-input mt-1 block w-full" value={form.default_frequency} onChange={(event) => setForm({ ...form, default_frequency: event.target.value })} placeholder="هر ۸ ساعت" />
            </div>
            <div>
              <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>مدت</label>
              <input className="field-input mt-1 block w-full" value={form.default_duration} onChange={(event) => setForm({ ...form, default_duration: event.target.value })} placeholder="۱۰ روز" />
            </div>
          </div>
          <div>
            <label className="text-xs font-bold" style={{ color: "var(--ink)" }}>دستور مصرف پیش‌فرض</label>
            <textarea rows={2} className="field-input mt-1" value={form.default_instructions} onChange={(event) => setForm({ ...form, default_instructions: event.target.value })} />
          </div>
          <button type="submit" className="btn-primary w-full !py-3">ذخیره دارو</button>
        </form>
      </section>
      <section className="panel overflow-hidden p-0">
        <div className="flex items-center justify-between border-b px-5 py-4" style={{ borderColor: "var(--line)" }}>
          <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>داروهای ثبت‌شده</h3>
          <span className="rounded-full px-3 py-1 text-xs font-bold" style={{ background: "var(--brand-soft)", color: "var(--brand-dark)" }}>{items.length}</span>
        </div>
        {items.length === 0 ? <div className="pp-empty">هنوز دارویی ثبت نشده.</div> : (
          <div className="divide-y" style={{ borderColor: "var(--line)" }}>
            {items.map((item) => {
              const edit = edits[item.id] || item;
              return (
                <details key={item.id} className="group px-5 py-3">
                  <summary className="flex cursor-pointer list-none items-center justify-between gap-2">
                    <div>
                      <strong style={{ color: "var(--ink)" }}>{item.name}</strong>
                      {item.dosage_form ? <span className="mr-2 text-xs" style={{ color: "var(--muted)" }}>{item.dosage_form}</span> : null}
                    </div>
                    <span className="text-xs" style={{ color: "var(--muted)" }}>{item.is_active === false ? "غیرفعال" : "فعال"}</span>
                  </summary>
                  <form className="mt-3 space-y-2 border-t pt-3" style={{ borderColor: "var(--line)" }} onSubmit={(event) => {
                    event.preventDefault();
                    void updateDrug(session, item.id, {
                      name: edit.name || "",
                      generic_name: edit.generic_name || "",
                      dosage_form: edit.dosage_form || "",
                      default_dosage: edit.default_dosage || "",
                      default_frequency: edit.default_frequency || "",
                      default_duration: edit.default_duration || "",
                      default_instructions: edit.default_instructions || "",
                      is_active: edit.is_active !== false,
                    }).then(refresh);
                  }}>
                    <input className="field-input !py-2 text-sm" value={edit.name || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, name: event.target.value } }))} required />
                    <input className="field-input !py-2 text-sm" value={edit.generic_name || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, generic_name: event.target.value } }))} placeholder="ژنریک" />
                    <div className="grid grid-cols-2 gap-2">
                      <input className="field-input !py-2 text-sm" value={edit.dosage_form || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, dosage_form: event.target.value } }))} placeholder="شکل" />
                      <input className="field-input !py-2 text-sm" value={edit.default_dosage || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, default_dosage: event.target.value } }))} placeholder="دوز" />
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <input className="field-input !py-2 text-sm" value={edit.default_frequency || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, default_frequency: event.target.value } }))} placeholder="دفعات" />
                      <input className="field-input !py-2 text-sm" value={edit.default_duration || ""} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, default_duration: event.target.value } }))} placeholder="مدت" />
                    </div>
                    <label className="flex items-center gap-2 text-xs">
                      <input type="checkbox" checked={edit.is_active !== false} onChange={(event) => setEdits((current) => ({ ...current, [item.id]: { ...edit, is_active: event.target.checked } }))} /> فعال
                    </label>
                    <button type="submit" className="btn-secondary !py-2 !text-xs">ذخیره</button>
                  </form>
                  <button type="button" className="mt-2 text-xs font-bold text-red-600" onClick={() => { if (confirm("حذف این دارو؟")) void destroyDrug(session, item.id).then(refresh); }}>حذف</button>
                </details>
              );
            })}
          </div>
        )}
      </section>
    </div>
  );
}
