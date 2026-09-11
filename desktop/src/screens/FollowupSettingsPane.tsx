import { FormEvent, useEffect, useState } from "react";
import {
  destroyFollowupStep,
  destroyFollowupTemplate,
  loadFollowupSettings,
  storeFollowupCatalog,
  storeFollowupStep,
  storeFollowupTemplate,
  updateFollowupCatalog,
  updateFollowupStep,
  updateFollowupTemplate,
  type FollowupCatalogRow,
  type FollowupTemplateDto,
} from "../lib/ops";
import type { Session } from "../lib/types";

export function FollowupSettingsPane({ session }: { session: Session }) {
  const [templates, setTemplates] = useState<FollowupTemplateDto[]>([]);
  const [kinds, setKinds] = useState<FollowupCatalogRow[]>([]);
  const [methods, setMethods] = useState<FollowupCatalogRow[]>([]);
  const [outcomes, setOutcomes] = useState<FollowupCatalogRow[]>([]);
  const [hospitals, setHospitals] = useState<Array<{ id: number; name?: string }>>([]);
  const [surgeryTypes, setSurgeryTypes] = useState<Array<{ id: number; name?: string; subtypes?: Array<{ id: number; name?: string }> }>>([]);
  const [staff, setStaff] = useState<Array<{ id: number; name?: string }>>([]);
  const [units, setUnits] = useState<Array<{ slug: string; label: string }>>([]);
  const [directions, setDirections] = useState<Array<{ slug: string; label: string }>>([]);
  const [events, setEvents] = useState<Array<{ slug: string; label: string }>>([]);
  const [canManage, setCanManage] = useState(!!session.user.can_manage_settings);
  const [notice, setNotice] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [applies, setApplies] = useState("surgery");
  const [hospitalId, setHospitalId] = useState("");
  const [typeId, setTypeId] = useState("");
  const [subtypeId, setSubtypeId] = useState("");
  const [description, setDescription] = useState("");
  const [newLabels, setNewLabels] = useState({ kind: "", method: "", outcome: "" });
  const [newSlugs, setNewSlugs] = useState({ kind: "", method: "", outcome: "" });

  async function refresh() {
    const data = await loadFollowupSettings(session);
    setTemplates(data.templates || []);
    setKinds(data.kinds || []);
    setMethods(data.methods || []);
    setOutcomes(data.outcomes || []);
    setHospitals(data.hospitals || []);
    setSurgeryTypes(data.surgery_types || []);
    setStaff(data.staff || []);
    setUnits(data.units || []);
    setDirections(data.directions || []);
    setEvents(data.reference_events || []);
    setCanManage(data.can_manage !== false && !!session.user.can_manage_settings);
  }

  useEffect(() => { void refresh(); }, [session]);

  const catalogs: Array<{ group: "kind" | "method" | "outcome"; title: string; items: FollowupCatalogRow[] }> = [
    { group: "kind", title: "انواع پیگیری", items: kinds },
    { group: "method", title: "روش‌های انجام", items: methods },
    { group: "outcome", title: "نتایج", items: outcomes },
  ];

  return (
    <div className="space-y-5">
      <p className="text-sm leading-7" style={{ color: "var(--muted)" }}>
        برای هر نوع یا زیرگروه عمل یک الگو بسازید. اگر زیرگروه الگو داشته باشد همان استفاده می‌شود، وگرنه الگوی عمومی نوع عمل.
        الگوی مخصوص بیمارستان بر الگوی «همه بیمارستان‌ها» اولویت دارد؛ بنابراین می‌توانید زمان‌بندی هر بیمارستان را جدا تنظیم کنید.
        با افزودن یا ذخیرهٔ مرحله، پیگیری برای نوبت‌های عمل و ویزیتِ موجود (از ۹۰ روز پیش به بعد) هم ساخته می‌شود؛ نوبت‌های بعدی هم خودکار می‌آیند.
      </p>
      {notice ? <p className="text-xs font-bold" style={{ color: "var(--brand-dark)" }}>{notice}</p> : null}

      <div className="grid gap-4 lg:grid-cols-2">
        {catalogs.map((pack) => (
          <section key={pack.group} className={`panel p-4 ${pack.group === "outcome" ? "lg:col-span-2" : ""}`}>
            <h3 className="mb-3 text-sm font-bold" style={{ color: "var(--ink)" }}>{pack.title}</h3>
            <div className="space-y-2">
              {pack.items.map((row) => (
                <CatalogRow key={row.id} session={session} row={row} canManage={canManage} onSaved={refresh} />
              ))}
            </div>
            {canManage ? (
              <form className="mt-3 flex flex-wrap gap-2" onSubmit={(event) => {
                event.preventDefault();
                void storeFollowupCatalog(session, {
                  group: pack.group,
                  label: newLabels[pack.group],
                  slug: newSlugs[pack.group] || newLabels[pack.group].replace(/\s+/g, "_"),
                }).then(() => {
                  setNewLabels((current) => ({ ...current, [pack.group]: "" }));
                  setNewSlugs((current) => ({ ...current, [pack.group]: "" }));
                  return refresh();
                });
              }}>
                <input className="field-input flex-1" placeholder="برچسب جدید" value={newLabels[pack.group]} onChange={(event) => setNewLabels((current) => ({ ...current, [pack.group]: event.target.value }))} required />
                <input className="field-input w-32" placeholder="slug" dir="ltr" value={newSlugs[pack.group]} onChange={(event) => setNewSlugs((current) => ({ ...current, [pack.group]: event.target.value }))} />
                <button type="submit" className="btn-secondary !px-3">+</button>
              </form>
            ) : null}
          </section>
        ))}
      </div>

      {canManage ? (
        <section className="panel p-4">
          <h3 className="mb-3 text-sm font-bold" style={{ color: "var(--ink)" }}>الگوی جدید</h3>
          <form className="grid gap-2 sm:grid-cols-2 lg:grid-cols-6" onSubmit={(event: FormEvent) => {
            event.preventDefault();
            void storeFollowupTemplate(session, {
              name,
              applies_to: applies,
              description,
              hospital_id: applies === "surgery" ? Number(hospitalId) || undefined : undefined,
              surgery_type_id: applies === "surgery" ? Number(typeId) || undefined : undefined,
              surgery_subtype_id: applies === "surgery" ? Number(subtypeId) || undefined : undefined,
            }).then(() => {
              setName("");
              setDescription("");
              setNotice("الگوی پیگیری ساخته شد. مراحل را اضافه کنید.");
              return refresh();
            });
          }}>
            <input className="field-input" placeholder="نام الگو" value={name} onChange={(event) => setName(event.target.value)} required />
            <select className="field-input" value={applies} onChange={(event) => setApplies(event.target.value)}>
              <option value="surgery">عمل</option>
              <option value="visit">ویزیت</option>
            </select>
            {applies === "surgery" ? (
              <>
                <select className="field-input" value={hospitalId} onChange={(event) => setHospitalId(event.target.value)}>
                  <option value="">همه بیمارستان‌ها</option>
                  {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                </select>
                <select className="field-input" value={typeId} onChange={(event) => { setTypeId(event.target.value); setSubtypeId(""); }}>
                  <option value="">نوع عمل</option>
                  {surgeryTypes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                </select>
                <select className="field-input" value={subtypeId} onChange={(event) => setSubtypeId(event.target.value)}>
                  <option value="">زیرگروه (اختیاری / اولویت بالاتر)</option>
                  {surgeryTypes.flatMap((type) => (type.subtypes || []).map((sub) => (
                    <option key={sub.id} value={sub.id}>{type.name} · {sub.name}</option>
                  )))}
                </select>
              </>
            ) : null}
            <button type="submit" className="btn-primary">ساخت الگو</button>
            <textarea rows={2} className="field-input sm:col-span-2 lg:col-span-6" placeholder="توضیح الگو (اختیاری)" value={description} onChange={(event) => setDescription(event.target.value)} />
          </form>
        </section>
      ) : null}

      <div className="space-y-4">
        {templates.length === 0 ? <div className="panel p-6 text-sm" style={{ color: "var(--muted)" }}>هنوز الگویی ساخته نشده.</div> : templates.map((template) => (
          <TemplateCard
            key={template.id}
            session={session}
            template={template}
            canManage={canManage}
            kinds={kinds}
            methods={methods}
            hospitals={hospitals}
            staff={staff}
            units={units}
            directions={directions}
            events={events}
            onSaved={(message) => { setNotice(message || "ذخیره شد."); return refresh(); }}
          />
        ))}
      </div>
    </div>
  );
}

function CatalogRow({
  session,
  row,
  canManage,
  onSaved,
}: {
  session: Session;
  row: FollowupCatalogRow;
  canManage: boolean;
  onSaved: () => Promise<unknown>;
}) {
  const [label, setLabel] = useState(row.label);
  const [sort, setSort] = useState(String(row.sort_order ?? 0));
  const [active, setActive] = useState(row.is_active !== false);
  useEffect(() => {
    setLabel(row.label);
    setSort(String(row.sort_order ?? 0));
    setActive(row.is_active !== false);
  }, [row]);
  return (
    <form className="flex flex-wrap items-center gap-2" onSubmit={(event) => {
      event.preventDefault();
      void updateFollowupCatalog(session, row.id, { label, sort_order: Number(sort) || 0, is_active: active }).then(onSaved);
    }}>
      <input className="field-input min-w-0 flex-1" value={label} onChange={(event) => setLabel(event.target.value)} disabled={!canManage} required />
      <input type="number" className="field-input w-20" value={sort} onChange={(event) => setSort(event.target.value)} disabled={!canManage} />
      <label className="flex items-center gap-1 text-[11px] font-bold" style={{ color: "var(--muted)" }}>
        <input type="checkbox" checked={active} disabled={!canManage} onChange={(event) => setActive(event.target.checked)} />
        فعال
      </label>
      {canManage ? <button type="submit" className="btn-secondary !px-2 !py-1 !text-[10px]">ذخیره</button> : null}
    </form>
  );
}

function TemplateCard({
  session,
  template,
  canManage,
  kinds,
  methods,
  hospitals,
  staff,
  units,
  directions,
  events,
  onSaved,
}: {
  session: Session;
  template: FollowupTemplateDto;
  canManage: boolean;
  kinds: FollowupCatalogRow[];
  methods: FollowupCatalogRow[];
  hospitals: Array<{ id: number; name?: string }>;
  staff: Array<{ id: number; name?: string }>;
  units: Array<{ slug: string; label: string }>;
  directions: Array<{ slug: string; label: string }>;
  events: Array<{ slug: string; label: string }>;
  onSaved: (message?: string) => Promise<unknown>;
}) {
  const [active, setActive] = useState(template.is_active !== false);
  const [hospitalId, setHospitalId] = useState(template.hospital_id ? String(template.hospital_id) : "");
  const [stepTitle, setStepTitle] = useState("");
  const [stepKind, setStepKind] = useState(kinds[0]?.slug || "");
  const [stepMethod, setStepMethod] = useState(methods[0]?.slug || "");
  const [amount, setAmount] = useState("1");
  const [unit, setUnit] = useState("day");
  const [direction, setDirection] = useState("after");
  const [event, setEvent] = useState(template.applies_to === "visit" ? "appointment_date" : "surgery_date");
  const [assignee, setAssignee] = useState("");

  useEffect(() => {
    setActive(template.is_active !== false);
    setHospitalId(template.hospital_id ? String(template.hospital_id) : "");
  }, [template]);

  return (
    <section className="panel overflow-hidden p-0">
      <div className="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3" style={{ borderColor: "var(--line)" }}>
        <div>
          <h3 className="text-sm font-bold" style={{ color: "var(--ink)" }}>{template.name}</h3>
          <p className="text-[11px]" style={{ color: "var(--muted)" }}>{template.binding_label} · {template.steps?.length || 0} مرحله</p>
        </div>
        {canManage ? (
          <div className="flex flex-wrap items-center gap-2">
            {template.applies_to === "surgery" ? (
              <select className="field-input !min-h-8 !py-1 !text-[11px]" value={hospitalId} onChange={(event) => setHospitalId(event.target.value)}>
                <option value="">همه بیمارستان‌ها</option>
                {hospitals.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
              </select>
            ) : null}
            <label className="flex items-center gap-1 text-[11px] font-bold">
              <input type="checkbox" checked={active} onChange={(event) => setActive(event.target.checked)} />
              فعال
            </label>
            <button type="button" className="btn-secondary !px-2 !py-1 !text-[10px]" onClick={() => void updateFollowupTemplate(session, template.id, { name: template.name, is_active: active, hospital_id: hospitalId ? Number(hospitalId) : null }).then((result) => onSaved(result && typeof result === "object" && "message" in result ? String(result.message) : "الگو ذخیره شد."))}>ذخیره وضعیت</button>
            <button type="button" className="btn-secondary !px-2 !py-1 !text-[10px]" style={{ color: "#b91c1c" }} onClick={() => { if (confirm("الگو و همه پیگیری‌های ساخته‌شده از آن حذف شوند؟")) void destroyFollowupTemplate(session, template.id).then(() => onSaved("الگو حذف شد.")); }}>حذف الگو</button>
          </div>
        ) : null}
      </div>
      <div className="space-y-3 p-4">
        {template.description ? <p className="text-xs" style={{ color: "var(--muted)" }}>{template.description}</p> : null}
        {(template.steps || []).map((step) => (
          <StepRow
            key={step.id}
            session={session}
            step={step}
            canManage={canManage}
            kinds={kinds}
            methods={methods}
            staff={staff}
            units={units}
            directions={directions}
            events={events}
            onSaved={onSaved}
          />
        ))}
        {canManage ? (
          <form className="grid gap-2 rounded-xl border border-dashed p-3 sm:grid-cols-2 lg:grid-cols-4" style={{ borderColor: "var(--line)" }} onSubmit={(formEvent) => {
            formEvent.preventDefault();
            void storeFollowupStep(session, template.id, {
              title: stepTitle,
              kind: stepKind,
              method: stepMethod,
              offset_amount: Number(amount) || 0,
              offset_unit: unit,
              offset_direction: direction,
              reference_event: event,
              assigned_user_id: assignee ? Number(assignee) : null,
            }).then((result) => {
              setStepTitle("");
              return onSaved(result && typeof result === "object" && "message" in result ? String(result.message) : "مرحله اضافه شد.");
            });
          }}>
            <input className="field-input lg:col-span-2" placeholder="عنوان مرحله جدید" value={stepTitle} onChange={(formEvent) => setStepTitle(formEvent.target.value)} required />
            <select className="field-input" value={stepKind} onChange={(formEvent) => setStepKind(formEvent.target.value)}>
              {kinds.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
            </select>
            <select className="field-input" value={stepMethod} onChange={(formEvent) => setStepMethod(formEvent.target.value)}>
              {methods.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
            </select>
            <input type="number" min={0} className="field-input" value={amount} onChange={(formEvent) => setAmount(formEvent.target.value)} required />
            <select className="field-input" value={unit} onChange={(formEvent) => setUnit(formEvent.target.value)}>
              {units.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
            </select>
            <select className="field-input" value={direction} onChange={(formEvent) => setDirection(formEvent.target.value)}>
              {directions.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
            </select>
            <select className="field-input" value={event} onChange={(formEvent) => setEvent(formEvent.target.value)}>
              {events.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
            </select>
            <select className="field-input lg:col-span-3" value={assignee} onChange={(formEvent) => setAssignee(formEvent.target.value)}>
              <option value="">مسئول پیش‌فرض ندارد</option>
              {staff.map((row) => <option key={row.id} value={row.id}>{row.name}</option>)}
            </select>
            <button type="submit" className="btn-primary lg:col-span-4">افزودن مرحله</button>
          </form>
        ) : null}
      </div>
    </section>
  );
}

function StepRow({
  session,
  step,
  canManage,
  kinds,
  methods,
  staff,
  units,
  directions,
  events,
  onSaved,
}: {
  session: Session;
  step: NonNullable<FollowupTemplateDto["steps"]>[number];
  canManage: boolean;
  kinds: FollowupCatalogRow[];
  methods: FollowupCatalogRow[];
  staff: Array<{ id: number; name?: string }>;
  units: Array<{ slug: string; label: string }>;
  directions: Array<{ slug: string; label: string }>;
  events: Array<{ slug: string; label: string }>;
  onSaved: (message?: string) => Promise<unknown>;
}) {
  const [title, setTitle] = useState(step.title || "");
  const [kind, setKind] = useState(step.kind || "");
  const [method, setMethod] = useState(step.method || "");
  const [amount, setAmount] = useState(String(step.offset_amount ?? 1));
  const [unit, setUnit] = useState(step.offset_unit || "day");
  const [direction, setDirection] = useState(step.offset_direction || "after");
  const [event, setEvent] = useState(step.reference_event || "surgery_date");
  const [assignee, setAssignee] = useState(step.assigned_user_id ? String(step.assigned_user_id) : "");
  const [sort, setSort] = useState(String(step.sort_order ?? 0));

  if (!canManage) {
    return (
      <div className="rounded-xl border px-3 py-2 text-xs" style={{ borderColor: "var(--line)" }}>
        <strong>{step.title}</strong>
        {step.timing_label ? <> · {step.timing_label}</> : null}
      </div>
    );
  }

  return (
    <form className="grid gap-2 rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-4" style={{ borderColor: "var(--line)" }} onSubmit={(formEvent) => {
      formEvent.preventDefault();
      void updateFollowupStep(session, step.id, {
        title,
        kind,
        method,
        offset_amount: Number(amount) || 0,
        offset_unit: unit,
        offset_direction: direction,
        reference_event: event,
        assigned_user_id: assignee ? Number(assignee) : null,
        sort_order: Number(sort) || 0,
      }).then(() => onSaved("مرحله ذخیره شد."));
    }}>
      <input className="field-input lg:col-span-2" value={title} onChange={(formEvent) => setTitle(formEvent.target.value)} required />
      <select className="field-input" value={kind} onChange={(formEvent) => setKind(formEvent.target.value)}>
        {kinds.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
      </select>
      <select className="field-input" value={method} onChange={(formEvent) => setMethod(formEvent.target.value)}>
        {methods.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
      </select>
      <input type="number" min={0} className="field-input" value={amount} onChange={(formEvent) => setAmount(formEvent.target.value)} required />
      <select className="field-input" value={unit} onChange={(formEvent) => setUnit(formEvent.target.value)}>
        {units.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
      </select>
      <select className="field-input" value={direction} onChange={(formEvent) => setDirection(formEvent.target.value)}>
        {directions.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
      </select>
      <select className="field-input" value={event} onChange={(formEvent) => setEvent(formEvent.target.value)}>
        {events.map((row) => <option key={row.slug} value={row.slug}>{row.label}</option>)}
      </select>
      <select className="field-input lg:col-span-2" value={assignee} onChange={(formEvent) => setAssignee(formEvent.target.value)}>
        <option value="">مسئول پیش‌فرض ندارد</option>
        {staff.map((row) => <option key={row.id} value={row.id}>{row.name}</option>)}
      </select>
      <input type="number" className="field-input" placeholder="ترتیب" value={sort} onChange={(formEvent) => setSort(formEvent.target.value)} />
      <div className="flex gap-2 lg:col-span-4">
        <button type="submit" className="btn-primary !py-1.5 !text-xs">ذخیره مرحله</button>
        <button type="button" className="btn-secondary !py-1.5 !text-xs" style={{ color: "#b91c1c" }} onClick={() => { if (confirm("حذف این مرحله؟")) void destroyFollowupStep(session, step.id).then(() => onSaved("مرحله حذف شد.")); }}>حذف مرحله</button>
      </div>
    </form>
  );
}
