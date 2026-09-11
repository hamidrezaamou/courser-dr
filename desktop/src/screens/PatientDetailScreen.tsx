import { FormEvent, PointerEvent, useEffect, useMemo, useRef, useState } from "react";
import { CacheHint, ErrorBanner, LoadingBox, StatusChip } from "../components/ui";
import { ApiError, loadPatient } from "../lib/api";
import { enqueueExam, enqueueNote, enqueueStatus, flushOutbox, overlayPatient, subscribeOutbox } from "../lib/outbox";
import { FollowupComposer } from "./ClinicPages";
import { ChecklistDrawer, ReadyAnswersPanel } from "./ClinicToolsPages";
import { destroyDocument, destroyExam, destroyNote, loadRxPrint, openPrintHtml, rotateDocument, storeDrawing, storePrescription, storeVoice, uploadDocuments } from "../lib/ops";
import { joinMeta } from "../lib/status";
import type { PatientDto, Session, TimelineItemDto } from "../lib/types";

type Panel = null | "photos" | "drawings" | "exams" | "rx" | "exam" | "profile" | "followup" | "whiteboard" | "upload";

function text(payload: Record<string, unknown> | null | undefined, key: string): string {
  const value = payload?.[key];
  return value == null ? "" : String(value);
}

function truthy(payload: Record<string, unknown> | null | undefined, key: string): boolean {
  return Boolean(payload?.[key]);
}

function itemDate(item: TimelineItemDto): string {
  const payload = item.payload || {};
  const jalali = text(payload, "created_at_jalali") || text(payload, "scheduled_date_jalali");
  return jalali.slice(0, 10) || "بدون تاریخ";
}

function examRows(payload: Record<string, unknown>): Array<{ label: string; value: string }> {
  const fields: Array<[string, string]> = [
    ["history", "شرح حال"],
    ["examination", "معاینه"],
    ["diagnosis", "تشخیص"],
    ["treatment", "درمان"],
    ["next_instruction", "دستور بعدی"],
    ["eye_side_label", "چشم"],
    ["va_right", "VA راست"],
    ["va_left", "VA چپ"],
    ["iop_right", "IOP راست"],
    ["iop_left", "IOP چپ"],
  ];
  return fields
    .map(([key, label]) => ({ label, value: text(payload, key) }))
    .filter((row) => row.value.trim());
}

function groupByDate(items: TimelineItemDto[]): Array<{ date: string; rows: TimelineItemDto[] }> {
  const chrono = [...items].reverse();
  const groups: Array<{ date: string; rows: TimelineItemDto[] }> = [];
  for (const item of chrono) {
    const date = itemDate(item);
    const last = groups[groups.length - 1];
    if (last && last.date === date) last.rows.push(item);
    else groups.push({ date, rows: [item] });
  }
  return groups;
}

function RxItems({ payload }: { payload: Record<string, unknown> }) {
  const items = Array.isArray(payload.items) ? payload.items : [];
  if (!items.length) return <p>{text(payload, "summary") || "نسخه دارو"}</p>;
  return (
    <div className="rx-list">
      {items.map((raw, index) => {
        const item = (raw || {}) as Record<string, unknown>;
        return (
          <div key={index} className="rx-item">
            <strong>{String(item.drug || "دارو")}</strong>
            {item.dosage ? <p>دوز: {String(item.dosage)}</p> : null}
            {item.frequency ? <p>دفعات: {String(item.frequency)}</p> : null}
            {item.instructions ? <p>{String(item.instructions)}</p> : null}
          </div>
        );
      })}
    </div>
  );
}

function TimelineBubble({
  item,
  onStatus,
  onDocument,
  onReady,
  onChecklist,
  onEdit,
  onPrints,
  onPrintRx,
  onDeleteExam,
  onDeleteNote,
}: {
  item: TimelineItemDto;
  onStatus?: (kind: string, id: number, status: string, prev?: string | null, label?: string | null) => void;
  onDocument?: (id: number, action: "rotate" | "delete") => void;
  onReady?: () => void;
  onChecklist?: () => void;
  onEdit?: () => void;
  onPrints?: () => void;
  onPrintRx?: () => void;
  onDeleteExam?: () => void;
  onDeleteNote?: () => void;
}) {
  const payload = item.payload || {};
  const when = text(payload, "created_at_jalali") || text(payload, "scheduled_date_jalali");

  if (item.type === "appointment" || item.type === "surgery") {
    const kind = item.type === "surgery" ? "نوبت عمل" : "نوبت ویزیت";
    const actions = Array.isArray(payload.actions) ? (payload.actions as Array<{ id?: string; label?: string }>) : [];
    const bookingId = Number(payload.id || 0);
    return (
      <article className="tg-bubble tg-bubble--staff">
        <div className="tg-bubble__meta">
          <span className={item.type === "surgery" ? "tg-bubble__tag is-surgery" : "tg-bubble__tag"}>{kind}</span>
          <span dir="ltr">{when}</span>
        </div>
        <strong>{text(payload, "title") || kind}</strong>
        <p className="meta">
          {joinMeta([
            text(payload, "scheduled_time_label"),
            text(payload, "hospital_name"),
            text(payload, "eye_side_label"),
            text(payload, "subtitle"),
            text(payload, "surgeon_name"),
          ])}
        </p>
        <div className="bubble-status">
          <StatusChip status={text(payload, "status")} label={text(payload, "status_label")} />
        </div>
        {onStatus && bookingId > 0 && !truthy(payload, "locked") && actions.length > 0 ? (
          <div className="action-row tight">
            {actions.map((action) => (
              <button
                key={action.id || action.label || ""}
                type="button"
                className="chip-btn"
                onClick={() => onStatus(item.type === "surgery" ? "surgery" : "visit", bookingId, action.id || "", text(payload, "status"), text(payload, "status_label"))}
              >
                {action.label}
              </button>
            ))}
          </div>
        ) : null}
        {onReady || (item.type === "surgery" && onChecklist) || onEdit || onPrints ? (
          <div className="action-row tight">
            {onEdit ? <button type="button" className="chip-btn" onClick={onEdit}>ویرایش نوبت</button> : null}
            {onReady ? <button type="button" className="chip-btn" onClick={onReady}>پاسخ آماده</button> : null}
            {item.type === "surgery" && onChecklist ? <button type="button" className="chip-btn" onClick={onChecklist}>چک‌لیست</button> : null}
            {item.type === "surgery" && onPrints ? <button type="button" className="chip-btn" onClick={onPrints}>چاپ برگه‌ها</button> : null}
          </div>
        ) : null}
      </article>
    );
  }

  if (item.type === "visit") {
    const rows = examRows(payload);
    const drawing = text(payload, "drawing_url");
    const voice = text(payload, "voice_url");
    const tag = truthy(payload, "has_voice") && !rows.length ? "ویس معاینه" : truthy(payload, "has_drawing") && !rows.length ? "وایت‌برد" : "معاینه";
    return (
      <article className="tg-bubble tg-bubble--staff">
        <div className="tg-bubble__meta">
          <span className="tg-bubble__tag">{tag}</span>
          <span dir="ltr">{when}</span>
        </div>
        {drawing ? <img className="tg-photo" src={drawing} alt="وایت‌برد" /> : null}
        {voice ? <audio className="tg-audio" controls src={voice} /> : null}
        {rows.length ? (
          <div className="tg-exam-fields">
            {rows.map((row) => (
              <div key={row.label} className="tg-exam-row">
                <div className="tg-exam-row__label">{row.label}</div>
                <div className="tg-exam-row__value">{row.value}</div>
              </div>
            ))}
          </div>
        ) : null}
        {!drawing && !voice && !rows.length ? <p className="hint">معاینه بدون جزئیات ثبت شد.</p> : null}
        {onDeleteExam ? (
          <div className="action-row tight">
            <button type="button" className="chip-btn" onClick={onDeleteExam}>حذف معاینه</button>
          </div>
        ) : null}
      </article>
    );
  }

  if (item.type === "note") {
    return (
      <article className="tg-bubble">
        <div className="tg-bubble__meta">
          <span className="tg-bubble__tag is-note">{text(payload, "creator_name") || "یادداشت داخلی"}</span>
          <span dir="ltr">{when}</span>
        </div>
        <p className="note-body">{text(payload, "note")}</p>
        {onDeleteNote ? (
          <div className="action-row tight">
            <button type="button" className="chip-btn" onClick={onDeleteNote}>حذف یادداشت</button>
          </div>
        ) : null}
      </article>
    );
  }

  if (item.type === "document") {
    const src = text(payload, "url");
    return (
      <article className="tg-bubble tg-bubble--staff">
        <div className="tg-bubble__meta">
          <span className="tg-bubble__tag">تصویر · {text(payload, "type") || "مدرک"}</span>
          <span dir="ltr">{when}</span>
        </div>
        {src ? <img className="tg-photo" src={src} alt={text(payload, "type") || "مدرک"} /> : null}
        {text(payload, "description") ? <p className="meta">{text(payload, "description")}</p> : null}
        {onDocument && Number(payload.id) > 0 ? (
          <div className="action-row tight">
            <button type="button" className="chip-btn" onClick={() => onDocument(Number(payload.id), "rotate")}>چرخش</button>
            <button type="button" className="chip-btn" onClick={() => onDocument(Number(payload.id), "delete")}>حذف</button>
          </div>
        ) : null}
      </article>
    );
  }

  if (item.type === "prescription") {
    return (
      <article className="tg-bubble tg-bubble--staff">
        <div className="tg-bubble__meta">
          <span className="tg-bubble__tag is-rx">نسخه دارو</span>
          <span dir="ltr">{when}</span>
        </div>
        <RxItems payload={payload} />
        {onPrintRx ? (
          <div className="action-row tight">
            <button type="button" className="chip-btn" onClick={onPrintRx}>چاپ نسخه</button>
          </div>
        ) : null}
      </article>
    );
  }

  return (
    <article className="tg-bubble">
      <p>{text(payload, "title") || item.type || "رویداد"}</p>
    </article>
  );
}

function ProfileSidebar({
  patient,
  timeline,
  staff,
  clinical,
  canEdit,
  onBookVisit,
  onBookSurgery,
  onOpenPanel,
  onEdit,
}: {
  patient: PatientDto;
  timeline: TimelineItemDto[];
  staff: boolean;
  clinical: boolean;
  canEdit: boolean;
  onBookVisit: () => void;
  onBookSurgery: () => void;
  onOpenPanel: (panel: Panel) => void;
  onEdit?: () => void;
}) {
  const [openId, setOpenId] = useState<string | null>(null);
  const photos = timeline.filter((item) => item.type === "document").length;
  const drawings = timeline.filter((item) => item.type === "visit" && truthy(item.payload, "has_drawing")).length;
  const exams = timeline.filter((item) => item.type === "visit").length;
  const rx = timeline.filter((item) => item.type === "prescription").length;
  const history = timeline
    .filter((item) => item.type === "appointment" || item.type === "surgery")
    .slice(0, 12);

  return (
    <>
      <div className="tg-side__card tg-side__card--profile">
        <div className="file-avatar">
          {patient.photo_url ? <img src={patient.photo_url} alt="" /> : <span>{(patient.initial || patient.name || "؟").slice(0, 1)}</span>}
        </div>
        <h2>{patient.name || "پرونده بیمار"}</h2>
        <p className="hint">پرونده الکترونیک</p>
        {staff ? (
          <>
            <div className="tg-attach-row">
              <button type="button" className="tg-attach-btn" onClick={() => onOpenPanel("photos")}>
                <span className="tg-attach-btn__icon is-photo">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path strokeLinecap="round" d="M7 17l3.5-4 2.5 2.5L15 13l4 4"/></svg>
                </span>
                <span className="tg-attach-btn__label">عکس بیمار</span>
                <span className="tg-attach-btn__count">{photos}</span>
              </button>
              <button type="button" className="tg-attach-btn" onClick={() => onOpenPanel("drawings")}>
                <span className="tg-attach-btn__icon is-draw">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.042.806"/></svg>
                </span>
                <span className="tg-attach-btn__label">وایت‌برد</span>
                <span className="tg-attach-btn__count">{drawings}</span>
              </button>
              <button type="button" className="tg-attach-btn" onClick={() => onOpenPanel("exams")}>
                <span className="tg-attach-btn__icon is-exam">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0117 8.414V19a2 2 0 01-2 2z"/></svg>
                </span>
                <span className="tg-attach-btn__label">معاینه</span>
                <span className="tg-attach-btn__count">{exams}</span>
              </button>
              {clinical ? (
                <button type="button" className="tg-attach-btn" onClick={() => onOpenPanel("rx")}>
                  <span className="tg-attach-btn__icon is-rx">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5"/></svg>
                  </span>
                  <span className="tg-attach-btn__label">دارو</span>
                  <span className="tg-attach-btn__count">{rx}</span>
                </button>
              ) : null}
            </div>
            <div className="file-actions">
              {canEdit ? <button type="button" className="btn-secondary btn-compact" onClick={onEdit}>ویرایش بیمار</button> : null}
              <button type="button" className="btn-secondary btn-compact" onClick={onBookSurgery}>نوبت عمل</button>
              <button type="button" className="btn-secondary btn-compact" onClick={onBookVisit}>ویزیت</button>
              <button type="button" className="btn-secondary btn-compact" onClick={() => onOpenPanel("followup")}>پیگیری</button>
            </div>
          </>
        ) : null}
      </div>

      <div className="tg-side__card">
        <div className="id-row"><span>کد ملی</span><strong dir="ltr">{patient.national_code || "—"}</strong></div>
        <div className="id-row"><span>موبایل</span><strong dir="ltr">{patient.mobile || "—"}</strong></div>
        {patient.mobile_secondary ? (
          <div className="id-row"><span>موبایل دوم</span><strong dir="ltr">{patient.mobile_secondary}</strong></div>
        ) : null}
        {patient.age ? (
          <div className="id-row"><span>سن</span><strong>{patient.age} سال</strong></div>
        ) : null}
      </div>

      <div className="tg-side__card">
        <h3>تاریخچه نوبت‌ها</h3>
        <div className="tg-side__history">
          {history.length === 0 ? <p className="hint">نوبتی ثبت نشده است.</p> : null}
          {history.map((item) => {
            const payload = item.payload || {};
            const key = item.id || `${item.type}-${text(payload, "id")}`;
            return (
              <div key={key} className="hist-row">
                <button type="button" className="hist-row__bar" onClick={() => setOpenId(openId === key ? null : key)}>
                  <span className={item.type === "surgery" ? "hist-kind is-surgery" : "hist-kind"}>
                    {item.type === "surgery" ? "عمل" : "ویزیت"}
                  </span>
                  <span className="hist-title">{text(payload, "title") || "نوبت"}</span>
                  <StatusChip status={text(payload, "status")} label={text(payload, "status_label")} />
                </button>
                {openId === key ? (
                  <div className="hist-row__body">
                    <div className="id-row"><span>تاریخ</span><span dir="ltr">{text(payload, "scheduled_date_jalali") || "—"}</span></div>
                    <div className="id-row"><span>ساعت/نوبت</span><span>{text(payload, "scheduled_time_label") || "—"}</span></div>
                    {text(payload, "hospital_name") ? (
                      <div className="id-row"><span>بیمارستان</span><span>{text(payload, "hospital_name")}</span></div>
                    ) : null}
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      </div>
    </>
  );
}

export function PatientDetailScreen({
  session,
  patientId,
  onBack,
  onBookVisit,
  onBookSurgery,
  onEdit,
  onEditBooking,
  onPrints,
}: {
  session: Session;
  patientId: number;
  onBack: () => void;
  onBookVisit: () => void;
  onBookSurgery: () => void;
  onEdit: () => void;
  onEditBooking?: (kind: "visit" | "surgery", id: number) => void;
  onPrints?: (surgeryId: number) => void;
}) {
  const staff = !!session.user.is_staff;
  const clinical = !!session.user.can_manage_clinical;
  const [data, setData] = useState(overlayPatient(patientId, null));
  const [fromCache, setFromCache] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [panel, setPanel] = useState<Panel>(null);
  const [note, setNote] = useState("");
  const [toolsOpen, setToolsOpen] = useState(false);
  const [exam, setExam] = useState({ history: "", examination: "", diagnosis: "", treatment: "", next_instruction: "", va_right: "", va_left: "", iop_right: "", iop_left: "", eye_side: "" });
  const [rxName, setRxName] = useState("");
  const [rxDose, setRxDose] = useState("");
  const [uploadType, setUploadType] = useState("عکس خارجی");
  const [voiceState, setVoiceState] = useState<"idle" | "recording" | "ready">("idle");
  const [voiceBlob, setVoiceBlob] = useState<Blob | null>(null);
  const recorderRef = useRef<MediaRecorder | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const feedRef = useRef<HTMLDivElement>(null);
  const [readyOpen, setReadyOpen] = useState(false);
  const [checkSurgeryId, setCheckSurgeryId] = useState<number | null>(null);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await loadPatient(session, patientId);
        if (!alive) return;
        setData(result.data);
        setFromCache(result.fromCache);
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

  useEffect(() => subscribeOutbox(() => {
    setData((current) => overlayPatient(patientId, current));
  }), [patientId]);

  const patient = data?.patient;
  const timeline = data?.timeline || [];
  const groups = useMemo(() => groupByDate(timeline), [timeline]);

  useEffect(() => {
    const feed = feedRef.current;
    if (feed) feed.scrollTop = feed.scrollHeight;
  }, [timeline.length, loading]);

  const mediaItems = (kind: Panel) => {
    if (kind === "photos") return timeline.filter((item) => item.type === "document");
    if (kind === "drawings") return timeline.filter((item) => item.type === "visit" && truthy(item.payload, "has_drawing"));
    if (kind === "exams") return timeline.filter((item) => item.type === "visit");
    if (kind === "rx") return timeline.filter((item) => item.type === "prescription");
    return [];
  };

  function refreshFile() {
    return loadPatient(session, patientId).then((result) => setData(result.data));
  }

  function handleDocument(id: number, action: "rotate" | "delete") {
    const job = action === "delete" ? destroyDocument(session, patientId, id) : rotateDocument(session, patientId, id);
    void job.then(refreshFile).catch((err) => setError(err instanceof ApiError ? err.message : "عملیات تصویر انجام نشد."));
  }

  async function startVoice() {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    const recorder = new MediaRecorder(stream);
    chunksRef.current = [];
    recorder.ondataavailable = (event) => { if (event.data.size) chunksRef.current.push(event.data); };
    recorder.onstop = () => {
      stream.getTracks().forEach((track) => track.stop());
      setVoiceBlob(new Blob(chunksRef.current, { type: recorder.mimeType || "audio/webm" }));
      setVoiceState("ready");
    };
    recorderRef.current = recorder;
    recorder.start();
    setVoiceState("recording");
  }

  function stopVoice() {
    recorderRef.current?.stop();
  }

  function sendVoice() {
    if (!voiceBlob) return;
    void storeVoice(session, patientId, voiceBlob)
      .then(() => { setVoiceBlob(null); setVoiceState("idle"); return refreshFile(); })
      .catch((err) => setError(err instanceof ApiError ? err.message : "ویس ذخیره نشد."));
  }

  function sendNote(event?: FormEvent) {
    event?.preventDefault();
    const value = note.trim();
    if (!value) return;
    enqueueNote(patientId, value);
    setNote("");
    void flushOutbox(session);
  }

  function sendExam(event: FormEvent) {
    event.preventDefault();
    const body = {
      history: exam.history.trim() || undefined,
      examination: exam.examination.trim() || undefined,
      diagnosis: exam.diagnosis.trim() || undefined,
      treatment: exam.treatment.trim() || undefined,
      next_instruction: exam.next_instruction.trim() || undefined,
      va_right: exam.va_right.trim() || undefined,
      va_left: exam.va_left.trim() || undefined,
      iop_right: exam.iop_right.trim() || undefined,
      iop_left: exam.iop_left.trim() || undefined,
      eye_side: exam.eye_side || undefined,
    };
    if (!Object.values(body).some(Boolean)) return;
    enqueueExam(patientId, body);
    setExam({ history: "", examination: "", diagnosis: "", treatment: "", next_instruction: "", va_right: "", va_left: "", iop_right: "", iop_left: "", eye_side: "" });
    setPanel(null);
    void flushOutbox(session);
  }

  return (
    <div className="tg-layout">
      <aside className="tg-side">
        {patient ? (
          <ProfileSidebar
            patient={patient}
            timeline={timeline}
            staff={staff}
            clinical={clinical}
            canEdit={!!session.user.can_edit_patient}
            onBookVisit={onBookVisit}
            onBookSurgery={onBookSurgery}
            onOpenPanel={setPanel}
            onEdit={onEdit}
          />
        ) : null}
      </aside>

      <section className="tg-chat">
        <header className="tg-chat__header">
          <div className="tg-chat__who">
            <button type="button" className="tg-tool" onClick={onBack} title="بازگشت" aria-label="بازگشت">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </button>
            <span className="file-avatar file-avatar--sm">
              {patient?.photo_url ? <img src={patient.photo_url} alt="" /> : <span>{(patient?.initial || patient?.name || "؟").slice(0, 1)}</span>}
            </span>
            <h1>{patient?.name || "پرونده بیمار"}</h1>
          </div>
          <button type="button" className="tg-tool tg-tool--mobile" onClick={() => setPanel("profile")} title="مشخصات">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
              <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
        </header>

        <div
          id="tg-feed"
          className="tg-chat__feed"
          ref={feedRef}
          onPaste={(event) => {
            if (!staff) return;
            const files = event.clipboardData?.files;
            if (!files?.length) return;
            event.preventDefault();
            void uploadDocuments(session, patientId, files, "عکس خارجی")
              .then(refreshFile)
              .catch((err) => setError(err instanceof ApiError ? err.message : "آپلود نشد."));
          }}
        >
          <CacheHint fromCache={fromCache} online={!fromCache} />
          <ErrorBanner message={error} />
          {loading && !patient ? <LoadingBox /> : null}
          <div className="tg-pill">شروع پرونده بیمار</div>
          {groups.map((group) => (
            <div key={group.date} className="tg-day">
              <div className="tg-pill">{group.date}</div>
              {group.rows.map((item) => (
                <TimelineBubble
                  key={item.id || `${item.type}-${itemDate(item)}`}
                  item={item}
                  onStatus={(kind, id, status, prev, label) => {
                    enqueueStatus(kind, id, status, prev, label);
                    void flushOutbox(session).then(refreshFile).catch(() => undefined);
                  }}
                  onDocument={handleDocument}
                  onReady={() => setReadyOpen(true)}
                  onChecklist={item.type === "surgery" ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0) setCheckSurgeryId(id);
                  } : undefined}
                  onEdit={item.type === "appointment" || item.type === "surgery" ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0) onEditBooking?.(item.type === "surgery" ? "surgery" : "visit", id);
                  } : undefined}
                  onPrints={item.type === "surgery" ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0) onPrints?.(id);
                  } : undefined}
                  onPrintRx={item.type === "prescription" ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0) void loadRxPrint(session, id).then((data) => data.html && openPrintHtml(data.html));
                  } : undefined}
                  onDeleteExam={item.type === "visit" && clinical ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0 && window.confirm("معاینه حذف شود؟")) {
                      void destroyExam(session, patientId, id).then(refreshFile);
                    }
                  } : undefined}
                  onDeleteNote={item.type === "note" && clinical ? () => {
                    const id = Number(item.payload?.id || 0);
                    if (id > 0 && window.confirm("یادداشت حذف شود؟")) {
                      void destroyNote(session, patientId, id).then(refreshFile);
                    }
                  } : undefined}
                />
              ))}
            </div>
          ))}
          {!loading && timeline.length === 0 ? <p className="hint empty-feed">هنوز ردیفی در پرونده نیست.</p> : null}
        </div>

        {staff ? (
          <div className="tg-composer">
            <form className="tg-composer__bar" onSubmit={sendNote}>
              {staff ? (
                <div className="tg-tools-menu-wrap">
                  {clinical ? (
                    <>
                      <button type="button" className="tg-tool tg-tool--menu" title={voiceState === "recording" ? "پایان ضبط" : "ضبط ویس"} onClick={() => voiceState === "recording" ? stopVoice() : void startVoice()}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" /></svg>
                      </button>
                    </>
                  ) : null}
                  <button type="button" className="tg-tool tg-tool--menu" title="ابزارهای بیشتر" onClick={() => setToolsOpen((open) => !open)}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                  </button>
                  {toolsOpen ? (
                    <div className="tg-tools-menu">
                      <button type="button" onClick={() => { setToolsOpen(false); setPanel("upload"); }}>آپلود تصویر</button>
                      {clinical ? <button type="button" onClick={() => { setToolsOpen(false); setPanel("exam"); }}>فرم معاینه</button> : null}
                      {clinical ? <button type="button" onClick={() => { setToolsOpen(false); setPanel("rx"); }}>نسخه دارو</button> : null}
                      {clinical ? <button type="button" onClick={() => { setToolsOpen(false); setPanel("whiteboard"); }}>وایت‌برد</button> : null}
                      <button type="button" onClick={() => { setToolsOpen(false); setPanel("followup"); }}>پیگیری</button>
                      <button type="button" onClick={() => { setToolsOpen(false); setReadyOpen(true); }}>پاسخ آماده</button>
                      <button type="button" onClick={() => { setToolsOpen(false); setPanel("photos"); }}>عکس بیمار</button>
                    </div>
                  ) : null}
                </div>
              ) : null}
              <textarea
                className="tg-composer__input"
                rows={1}
                value={note}
                placeholder="پیام برای پزشک، منشی و مدیر..."
                onChange={(event) => setNote(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === "Enter" && event.shiftKey) {
                    event.preventDefault();
                    sendNote();
                  }
                }}
              />
              <button type="submit" className="tg-composer__send" title="ارسال (Shift+Enter)" aria-label="ارسال">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
              </button>
            </form>
            <p className="composer-hint">Enter خط جدید · Shift+Enter ارسال · Ctrl+V برای چسباندن عکس</p>
            {voiceState === "recording" ? <p className="hint">در حال ضبط ویس...</p> : null}
            {voiceState === "ready" && voiceBlob ? (
              <div className="voice-preview">
                <audio controls src={URL.createObjectURL(voiceBlob)} />
                <div className="action-row tight">
                  <button type="button" className="btn-primary btn-compact" onClick={sendVoice}>ارسال ویس</button>
                  <button type="button" className="btn-ghost btn-compact" onClick={() => { setVoiceBlob(null); setVoiceState("idle"); }}>حذف</button>
                </div>
              </div>
            ) : null}
          </div>
        ) : null}
      </section>

      {panel ? (
        <div className="tg-drawer" onClick={() => setPanel(null)} role="presentation">
          <div className="tg-drawer__sheet" onClick={(event) => event.stopPropagation()} role="dialog">
            <header className="tg-drawer__head">
              <h2>
                {panel === "photos" ? "عکس بیمار" : panel === "drawings" || panel === "whiteboard" ? "وایت‌برد" : panel === "exams" ? "معاینه" : panel === "rx" ? "دارو" : panel === "exam" ? "فرم معاینه" : panel === "followup" ? "پیگیری" : panel === "upload" ? "آپلود تصویر" : "مشخصات بیمار"}
              </h2>
              <button type="button" className="btn-ghost btn-compact" onClick={() => setPanel(null)}>بستن</button>
            </header>
            {panel === "profile" && patient ? (
              <ProfileSidebar
                patient={patient}
                timeline={timeline}
                staff={staff}
                clinical={clinical}
                canEdit={!!session.user.can_edit_patient}
                onBookVisit={onBookVisit}
                onBookSurgery={onBookSurgery}
                onOpenPanel={setPanel}
                onEdit={onEdit}
              />
            ) : null}
            {panel === "exam" ? (
              <form className="form-stack" onSubmit={sendExam}>
                <label className="field"><span>شرح حال</span><textarea value={exam.history} onChange={(event) => setExam({ ...exam, history: event.target.value })} /></label>
                <label className="field"><span>معاینه</span><textarea value={exam.examination} onChange={(event) => setExam({ ...exam, examination: event.target.value })} /></label>
                <label className="field"><span>تشخیص</span><textarea value={exam.diagnosis} onChange={(event) => setExam({ ...exam, diagnosis: event.target.value })} /></label>
                <label className="field"><span>درمان</span><textarea value={exam.treatment} onChange={(event) => setExam({ ...exam, treatment: event.target.value })} /></label>
                <label className="field"><span>دستور بعدی</span><textarea value={exam.next_instruction} onChange={(event) => setExam({ ...exam, next_instruction: event.target.value })} /></label>
                <div className="form-grid">
                  <label className="field"><span>VA راست</span><input value={exam.va_right} onChange={(event) => setExam({ ...exam, va_right: event.target.value })} /></label>
                  <label className="field"><span>VA چپ</span><input value={exam.va_left} onChange={(event) => setExam({ ...exam, va_left: event.target.value })} /></label>
                  <label className="field"><span>IOP راست</span><input value={exam.iop_right} onChange={(event) => setExam({ ...exam, iop_right: event.target.value })} /></label>
                  <label className="field"><span>IOP چپ</span><input value={exam.iop_left} onChange={(event) => setExam({ ...exam, iop_left: event.target.value })} /></label>
                </div>
                <label className="field"><span>چشم</span>
                  <select value={exam.eye_side} onChange={(event) => setExam({ ...exam, eye_side: event.target.value })}>
                    <option value="">انتخاب نشده</option>
                    <option value="OD">راست</option>
                    <option value="OS">چپ</option>
                    <option value="OU">دو طرفه</option>
                  </select>
                </label>
                <button className="btn-primary" type="submit">ثبت معاینه</button>
              </form>
            ) : null}
            {panel === "photos" || panel === "drawings" || panel === "exams" || panel === "rx" ? (
              <div className="drawer-feed">
                {panel === "photos" ? (
                  <label className="field">
                    <span>افزودن عکس</span>
                    <input type="file" accept="image/*" multiple onChange={(event) => {
                      const files = event.target.files;
                      if (!files?.length) return;
                      void uploadDocuments(session, patientId, files, uploadType).then(refreshFile).catch((err) => setError(err instanceof ApiError ? err.message : "آپلود نشد."));
                    }} />
                  </label>
                ) : null}
                {panel === "rx" && clinical ? (
                  <form className="form-stack" onSubmit={(event) => {
                    event.preventDefault();
                    if (!rxName.trim()) return;
                    void storePrescription(session, patientId, { items: [{ drug_name: rxName, dosage: rxDose }] })
                      .then(refreshFile)
                      .then(() => { setRxName(""); setRxDose(""); })
                      .catch((err) => setError(err instanceof ApiError ? err.message : "نسخه ثبت نشد."));
                  }}>
                    <label className="field"><span>نام دارو</span><input value={rxName} onChange={(event) => setRxName(event.target.value)} /></label>
                    <label className="field"><span>دوز</span><input value={rxDose} onChange={(event) => setRxDose(event.target.value)} /></label>
                    <button className="btn-primary" type="submit">ثبت نسخه</button>
                  </form>
                ) : null}
                {mediaItems(panel === "photos" || panel === "drawings" || panel === "exams" || panel === "rx" ? panel : "photos").map((item) => (
                  <TimelineBubble key={item.id || `${item.type}-${text(item.payload, "id")}`} item={item} onDocument={handleDocument} />
                ))}
                {mediaItems(panel === "photos" || panel === "drawings" || panel === "exams" || panel === "rx" ? panel : "photos").length === 0 ? <p className="hint">موردی در این بخش نیست.</p> : null}
              </div>
            ) : null}
            {panel === "followup" ? <FollowupComposer session={session} patientId={patientId} onDone={() => { setPanel(null); void refreshFile(); }} /> : null}
            {panel === "upload" ? (
              <div className="form-stack">
                <label className="field"><span>نوع تصویر</span>
                  <select value={uploadType} onChange={(event) => setUploadType(event.target.value)}>
                    {["Fundus", "OCT", "آنژیو", "عکس خارجی", "اسکن", "نسخه", "سایر"].map((item) => <option key={item} value={item}>{item}</option>)}
                  </select>
                </label>
                <label className="field">
                  <span>فایل</span>
                  <input type="file" accept="image/*" multiple onChange={(event) => {
                    const files = event.target.files;
                    if (!files?.length) return;
                    void uploadDocuments(session, patientId, files, uploadType).then(() => { setPanel(null); return refreshFile(); }).catch((err) => setError(err instanceof ApiError ? err.message : "آپلود نشد."));
                  }} />
                </label>
              </div>
            ) : null}
            {panel === "whiteboard" && clinical ? (
              <WhiteboardPad
                onSave={(image) => {
                  void storeDrawing(session, patientId, image).then(refreshFile).then(() => setPanel(null));
                }}
              />
            ) : null}
          </div>
        </div>
      ) : null}
      {readyOpen ? (
        <ReadyAnswersPanel
          session={session}
          target={{ patientId, patientName: patient?.name, mobile: patient?.mobile, nationalCode: patient?.national_code }}
          onClose={() => setReadyOpen(false)}
        />
      ) : null}
      {checkSurgeryId ? <ChecklistDrawer session={session} surgeryId={checkSurgeryId} onClose={() => setCheckSurgeryId(null)} /> : null}
    </div>
  );
}

function WhiteboardPad({ onSave }: { onSave: (image: string) => void }) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const drawing = useRef(false);

  function point(event: PointerEvent<HTMLCanvasElement>) {
    const canvas = canvasRef.current;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    return {
      x: (event.clientX - rect.left) * (canvas.width / rect.width),
      y: (event.clientY - rect.top) * (canvas.height / rect.height),
    };
  }

  return (
    <div className="whiteboard">
      <canvas
        ref={canvasRef}
        width={720}
        height={480}
        className="whiteboard__canvas"
        onPointerDown={(event) => {
          drawing.current = true;
          const ctx = canvasRef.current?.getContext("2d");
          if (!ctx) return;
          const { x, y } = point(event);
          ctx.beginPath();
          ctx.moveTo(x, y);
        }}
        onPointerMove={(event) => {
          if (!drawing.current) return;
          const ctx = canvasRef.current?.getContext("2d");
          if (!ctx) return;
          const { x, y } = point(event);
          ctx.lineWidth = 2.4;
          ctx.lineCap = "round";
          ctx.strokeStyle = "#1e3a5f";
          ctx.lineTo(x, y);
          ctx.stroke();
        }}
        onPointerUp={() => { drawing.current = false; }}
        onPointerLeave={() => { drawing.current = false; }}
      />
      <div className="action-row">
        <button type="button" className="btn-ghost" onClick={() => {
          const canvas = canvasRef.current;
          const ctx = canvas?.getContext("2d");
          if (canvas && ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
        }}>پاک کردن</button>
        <button type="button" className="btn-primary" onClick={() => {
          const data = canvasRef.current?.toDataURL("image/png");
          if (data) onSave(data);
        }}>ذخیره وایت‌برد</button>
      </div>
    </div>
  );
}
