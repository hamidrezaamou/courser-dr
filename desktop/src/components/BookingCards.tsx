import { useEffect, useState } from "react";
import { boardTintClass, bookingMeta, kindLine, placeLine, telHref, turnLabel, weekdayLabel } from "../lib/bookingUi";
import { destroyReportNote, loadReportNotes, storeReportNote, toggleReportNotePrint, type ReportNoteDto } from "../lib/ops";
import type { BookingDto, Session } from "../lib/types";
import { RowToolbox, type BookingNav } from "./RowToolbox";

function Chevron() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" className="board-card__chevron" aria-hidden="true">
      <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
    </svg>
  );
}

function PhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
    </svg>
  );
}

export function BoardAppointmentCard({
  item,
  index,
  session,
  nav,
  onReady,
  onChecklist,
  onApplied,
}: {
  item: BookingDto;
  index: number;
  session: Session;
  nav: BookingNav;
  onReady: () => void;
  onChecklist?: () => void;
  onApplied?: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [tools, setTools] = useState(false);
  const phone = telHref(item.mobile);
  const isSurgery = item.kind === "surgery";

  return (
    <>
      <article className={`board-card ${boardTintClass(item.status)}${open ? " is-open" : ""}`}>
        <div className="board-card__turn" title={item.scheduled_time_label || ""}>
          <span>{turnLabel(item, index)}</span>
        </div>
        <div className="board-card__shell">
          <div
            className="board-card__bar"
            role="button"
            tabIndex={0}
            onClick={() => setOpen((current) => !current)}
            onKeyDown={(event) => {
              if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                setOpen((current) => !current);
              }
            }}
          >
            <div className="board-card__bar-main">
              <span
                className="board-card__name board-card__name--toolbox"
                role="button"
                tabIndex={0}
                onClick={(event) => {
                  event.stopPropagation();
                  setTools(true);
                }}
              >
                {item.patient_name || "—"}
              </span>
              <p className="board-card__kind">{kindLine(item)}</p>
            </div>
            <div className="board-card__bar-side">
              <span className="board-card__type">{isSurgery ? "عمل" : "ویزیت"}</span>
              <span className="board-card__badge">{item.status_label || "—"}</span>
              <span className="board-card__bar-when" dir="ltr">
                {item.scheduled_date_jalali} · {item.scheduled_time_label}
              </span>
              <Chevron />
            </div>
          </div>
          {open ? (
            <div className="board-card__drawer">
              <div className="board-card__body">
                <div className="board-card__identity">
                  <p className="board-card__place">{placeLine(item)}</p>
                  <p className="board-card__id ltr-data"><span dir="ltr">{item.national_code || "—"}</span></p>
                </div>
                <div className="board-card__mid">
                  <p className="board-card__date" dir="ltr">{item.scheduled_date_jalali || "—"}</p>
                  <p className="board-card__slot" dir="ltr">{item.scheduled_time_label || "—"}</p>
                  {phone ? (
                    <a href={phone} className="board-card__phone" dir="ltr">
                      <PhoneIcon />
                      {item.mobile}
                    </a>
                  ) : (
                    <span className="board-card__phone is-muted" dir="ltr">{item.mobile || "—"}</span>
                  )}
                </div>
                <div className="board-card__actions">
                  {item.actions && item.actions.length > 0 && !item.locked ? (
                    <div className="board-card__acts">
                      {item.actions.map((action) => (
                        <button
                          key={action.id || action.label || ""}
                          type="button"
                          className="chip-btn"
                          onClick={() => setTools(true)}
                        >
                          {action.label}
                        </button>
                      ))}
                    </div>
                  ) : null}
                  <button type="button" className="row-toolbox-trigger board-toolbox-trigger" onClick={() => setTools(true)}>
                    ابزار
                  </button>
                </div>
              </div>
            </div>
          ) : null}
        </div>
      </article>
      {tools ? (
        <RowToolbox
          session={session}
          item={item}
          onClose={() => setTools(false)}
          nav={nav}
          onReady={onReady}
          onChecklist={onChecklist}
          onApplied={onApplied}
        />
      ) : null}
    </>
  );
}

export function ReportAppointmentCard({
  item,
  session,
  nav,
  bulk,
  selected,
  onToggle,
  onReady,
  onChecklist,
  onNotes,
  onApplied,
}: {
  item: BookingDto;
  session: Session;
  nav: BookingNav;
  bulk?: boolean;
  selected?: boolean;
  onToggle?: () => void;
  onReady: () => void;
  onChecklist?: () => void;
  onNotes: () => void;
  onApplied?: () => void;
}) {
  const [tools, setTools] = useState(false);
  const isSurgery = item.kind === "surgery";
  const notes = item.note_count || 0;

  return (
    <>
      <article
        className={`report-card ${isSurgery ? "is-surgery" : "is-visit"}${selected ? " is-bulk-selected" : ""}`}
        onClick={bulk ? onToggle : undefined}
      >
        <header className="report-card__head">
          <div className="report-card__kind">
            {bulk ? (
              <span className="report-bulk-check-wrap">
                <button
                  type="button"
                  className={`report-bulk-toggle${selected ? " is-on" : ""}`}
                  onClick={(event) => {
                    event.stopPropagation();
                    onToggle?.();
                  }}
                >
                  <span className="report-bulk-toggle__ui" />
                </button>
              </span>
            ) : null}
            <span className={`report-kind ${isSurgery ? "is-surgery" : "is-visit"}`}>{isSurgery ? "عمل" : "ویزیت"}</span>
            {isSurgery && item.is_emergency ? <span className="report-emergency-badge">اورژانس</span> : null}
          </div>
          <span className={`report-status is-${item.status || "scheduled"}`}>{item.status_label || "—"}</span>
        </header>
        <button type="button" className="report-card__name" onClick={() => setTools(true)}>
          {item.patient_name || "—"}
        </button>
        <p className="report-card__meta">
          <span className="mono ltr-data">{item.scheduled_date_jalali}</span>
          {weekdayLabel(item) ? <span>{weekdayLabel(item)}</span> : null}
          <span className="mono strong-slot ltr-data">{item.scheduled_time_label}</span>
        </p>
        <p className="report-card__center">
          <strong>{isSurgery ? item.hospital_name || "—" : "ویزیت مطب"}</strong>
          <span>{kindLine(item)}</span>
        </p>
        <div className="report-card__row">
          <span className="mono ltr-data">{item.national_code || "—"}</span>
          <div className="report-phones">
            {item.mobile ? <span dir="ltr">{item.mobile}</span> : null}
            {item.mobile_secondary ? <span dir="ltr">{item.mobile_secondary}</span> : null}
          </div>
        </div>
        <footer className="report-card__foot">
          {notes > 0 ? (
            <button type="button" className="report-note-icon" onClick={onNotes} title="مشاهده توضیحات">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
              </svg>
              <span className="report-note-count">{notes}</span>
            </button>
          ) : (
            <button type="button" className="report-card__note-add" onClick={onNotes}>یادداشت</button>
          )}
          {!bulk ? (
            <button type="button" className="row-toolbox-trigger" onClick={() => setTools(true)}>ابزار</button>
          ) : null}
        </footer>
      </article>
      {tools ? (
        <RowToolbox
          session={session}
          item={item}
          mode="report"
          onClose={() => setTools(false)}
          nav={nav}
          onReady={onReady}
          onChecklist={onChecklist}
          onNotes={onNotes}
          onApplied={onApplied}
        />
      ) : null}
    </>
  );
}

export function ReportTableRow({
  item,
  index,
  session,
  nav,
  bulk,
  selected,
  onToggle,
  onReady,
  onChecklist,
  onNotes,
  onApplied,
}: {
  item: BookingDto;
  index: number;
  session: Session;
  nav: BookingNav;
  bulk?: boolean;
  selected?: boolean;
  onToggle?: () => void;
  onReady: () => void;
  onChecklist?: () => void;
  onNotes: () => void;
  onApplied?: () => void;
}) {
  const [tools, setTools] = useState(false);
  const isSurgery = item.kind === "surgery";
  const notes = item.note_count || 0;
  return (
    <>
      <tr className={selected ? "is-bulk-selected" : undefined}>
        <td className="col-bulk">
          {bulk ? (
            <button type="button" className={`report-bulk-toggle${selected ? " is-on" : ""}`} onClick={onToggle}>
              <span className="report-bulk-toggle__ui" />
            </button>
          ) : null}
        </td>
        <td className="col-num">{index + 1}</td>
        <td className="col-id mono">{item.id}</td>
        <td>
          <span className={`report-kind ${isSurgery ? "is-surgery" : "is-visit"}`}>{isSurgery ? "عمل" : "ویزیت"}</span>
          {item.is_emergency ? <span className="report-emergency-badge">اورژانس</span> : null}
        </td>
        <td>
          <button type="button" className="plain-link" onClick={() => setTools(true)}>
            <strong>{item.patient_name || "—"}</strong>
          </button>
        </td>
        <td className="mono ltr-data">{item.national_code || "—"}</td>
        <td>
          <div className="col-detail-main">{isSurgery ? item.hospital_name || "—" : "ویزیت مطب"}</div>
          <div className="col-detail-sub">{kindLine(item)}</div>
        </td>
        <td className="mono ltr-data">{item.scheduled_date_jalali}</td>
        <td className="mono strong-slot ltr-data">{item.scheduled_time_label}</td>
        <td>
          <div className="report-phones">
            {item.mobile ? <span dir="ltr">{item.mobile}</span> : null}
            {item.mobile_secondary ? <span dir="ltr">{item.mobile_secondary}</span> : null}
          </div>
        </td>
        <td><span className={`report-status is-${item.status || "scheduled"}`}>{item.status_label}</span></td>
        <td className="col-note">
          <button type="button" className={notes ? "report-note-icon" : "report-card__note-add"} onClick={onNotes}>
            {notes ? notes : "یادداشت"}
          </button>
        </td>
        <td className="col-actions">
          {!bulk ? <button type="button" className="row-toolbox-trigger" onClick={() => setTools(true)}>ابزار</button> : null}
        </td>
      </tr>
      {tools ? (
        <RowToolbox
          session={session}
          item={item}
          mode="report"
          onClose={() => setTools(false)}
          nav={nav}
          onReady={onReady}
          onChecklist={onChecklist}
          onNotes={onNotes}
          onApplied={onApplied}
        />
      ) : null}
    </>
  );
}

export function FloorAppointmentCard({
  item,
  session,
  nav,
  next,
  onReady,
  onChecklist,
  onApplied,
}: {
  item: BookingDto;
  session: Session;
  nav: BookingNav;
  next?: { status: string; label: string };
  onReady: () => void;
  onChecklist?: () => void;
  onApplied?: (status?: string) => void;
}) {
  const [tools, setTools] = useState(false);
  const isSurgery = item.kind === "surgery";
  return (
    <>
      <article className={`floor-card ${isSurgery ? "floor-card--surgery" : "floor-card--visit"}`}>
        <div className="floor-card__top">
          <span className="floor-card__time" dir="ltr">{item.scheduled_time_label || "—"}</span>
          <span className={`floor-card__type ${isSurgery ? "floor-card__type--surgery" : "floor-card__type--visit"}`}>
            {isSurgery ? "عمل" : "ویزیت"}
          </span>
        </div>
        <button type="button" className="floor-card__name" onClick={() => item.patient_id && nav.onOpenPatient(item.patient_id)}>
          {item.patient_name}
        </button>
        <p className="floor-card__kind">
          {kindLine(item)}
          {isSurgery && item.hospital_name ? <span className="floor-card__hospital"> · {item.hospital_name}</span> : null}
        </p>
        <p className="floor-card__status">{item.status_label}</p>
        <div className="action-row tight">
          {item.actions && item.actions.length > 0 && item.id > 0 && !item.locked ? (
            item.actions.map((action) => (
              <button key={action.id || action.label || ""} type="button" className="chip-btn" onClick={() => onApplied?.(action.id || "")}>
                {action.label}
              </button>
            ))
          ) : next && item.id > 0 && !item.locked ? (
            <button type="button" className="btn-secondary btn-compact" onClick={() => onApplied?.(next.status)}>
              {next.label}
            </button>
          ) : null}
          <button type="button" className="row-toolbox-trigger" onClick={() => setTools(true)}>ابزار</button>
        </div>
      </article>
      {tools ? (
        <RowToolbox
          session={session}
          item={item}
          mode="floor"
          onClose={() => setTools(false)}
          nav={nav}
          onReady={onReady}
          onChecklist={onChecklist}
          onApplied={() => onApplied?.()}
        />
      ) : null}
    </>
  );
}

export function ReportNotesDialog({ session, row, onClose }: { session: Session; row: BookingDto; onClose: () => void }) {
  const [notes, setNotes] = useState<ReportNoteDto[]>([]);
  const [body, setBody] = useState("");
  const kind = row.kind === "surgery" ? "surgery" : "visit";
  useEffect(() => {
    void loadReportNotes(session, kind, row.id).then((data) => setNotes(data.notes || [])).catch(() => setNotes([]));
  }, [session, row, kind]);
  return (
    <div className="modal-back" onClick={onClose} role="presentation">
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog">
        <h2>گفتگوی گزارش · {row.patient_name}</h2>
        <p className="hint">{bookingMeta(row)}</p>
        <div className="stack">
          {notes.map((note) => (
            <article key={note.id} className="card">
              <strong>{note.author || note.author_name}</strong>
              <p>{note.body}</p>
              <div className="action-row tight">
                <button
                  type="button"
                  className="chip-btn"
                  onClick={() =>
                    void toggleReportNotePrint(session, note.id, !note.include_in_print)
                      .then(() => loadReportNotes(session, kind, row.id))
                      .then((data) => setNotes(data.notes || []))
                  }
                >
                  {note.include_in_print ? "حذف از چاپ" : "چاپ در گزارش"}
                </button>
                <button
                  type="button"
                  className="chip-btn"
                  onClick={() =>
                    void destroyReportNote(session, note.id)
                      .then(() => loadReportNotes(session, kind, row.id))
                      .then((data) => setNotes(data.notes || []))
                  }
                >
                  حذف
                </button>
              </div>
            </article>
          ))}
        </div>
        <label className="field"><span>یادداشت</span><textarea value={body} onChange={(event) => setBody(event.target.value)} /></label>
        <div className="action-row">
          <button
            type="button"
            className="btn-primary"
            onClick={() =>
              void storeReportNote(session, { subject_type: kind, subject_id: row.id, body }).then(() => {
                setBody("");
                return loadReportNotes(session, kind, row.id);
              }).then((data) => setNotes(data.notes || []))
            }
          >
            ارسال
          </button>
          <button type="button" className="btn-ghost" onClick={onClose}>بستن</button>
        </div>
      </div>
    </div>
  );
}
