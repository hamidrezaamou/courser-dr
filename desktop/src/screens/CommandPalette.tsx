import { useEffect, useState } from "react";
import { ApiError, loadPatients } from "../lib/api";
import type { PatientDto, Session } from "../lib/types";

export function CommandPalette({
  session,
  onClose,
  onOpen,
}: {
  session: Session;
  onClose: () => void;
  onOpen: (id: number) => void;
}) {
  const [q, setQ] = useState("");
  const [items, setItems] = useState<PatientDto[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void (async () => {
        try {
          const result = await loadPatients(session, q);
          setItems(result.data.patients || []);
          setError(null);
        } catch (err) {
          setError(err instanceof ApiError ? err.message : "جستجو انجام نشد.");
        }
      })();
    }, 200);
    return () => window.clearTimeout(timer);
  }, [q, session]);

  return (
    <div className="modal-back" onClick={onClose} role="presentation">
      <div className="palette" onClick={(event) => event.stopPropagation()} role="dialog">
        <input
          autoFocus
          value={q}
          placeholder="Ctrl+K — نام، کد ملی، موبایل"
          onChange={(event) => setQ(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === "Escape") onClose();
            if (event.key === "Enter" && items[0]) {
              onOpen(items[0].id);
            }
          }}
        />
        {error ? <p className="hint">{error}</p> : null}
        <ul>
          {items.slice(0, 8).map((patient) => (
            <li key={patient.id}>
              <button type="button" onClick={() => onOpen(patient.id)}>
                <strong>{patient.name}</strong>
                <span>{[patient.national_code, patient.mobile].filter(Boolean).join(" · ")}</span>
              </button>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
