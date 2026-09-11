import { useEffect, useState } from "react";
import { ApiError, clinicRequest, isNetworkError } from "./client";
import type {
  BoardResponse,
  BookSurgeryBody,
  BookingDto,
  BookVisitBody,
  HomeResponse,
  PatientDetailResponse,
  PatientDto,
  PatientsResponse,
  Session,
  TimelineItemDto,
} from "./types";
import { STATUS_LABELS as labels } from "./types";

const KEY = "mramo-outbox-v1";
const MAP_KEY = "mramo-id-map-v1";

export type OutboxStatus = "pending" | "sending" | "failed" | "rejected";

export type OutboxJob =
  | {
      type: "create-patient";
      localId: number;
      body: { name: string; national_code: string; mobile: string; age?: string };
    }
  | { type: "book-visit"; patientId: number; localBookingId: number; body: BookVisitBody; slotLabel?: string }
  | { type: "book-surgery"; patientId: number; localBookingId: number; body: BookSurgeryBody; slotLabel?: string }
  | { type: "note"; patientId: number; localId: number; note: string }
  | {
      type: "exam";
      patientId: number;
      localId: number;
      body: {
        history?: string;
        examination?: string;
        diagnosis?: string;
        treatment?: string;
        next_instruction?: string;
        eye_side?: string;
        va_right?: string;
        va_left?: string;
        iop_right?: string;
        iop_left?: string;
      };
    }
  | {
      type: "status";
      kind: string;
      bookingId: number;
      status: string;
      prevStatus?: string | null;
      prevLabel?: string | null;
    };

export type OutboxItem = {
  id: string;
  createdAt: number;
  status: OutboxStatus;
  error?: string;
  job: OutboxJob;
};

const listeners = new Set<() => void>();
const idListeners = new Set<(from: number, to: number) => void>();
let flushing = false;

function emit() {
  listeners.forEach((fn) => fn());
}

export function subscribeOutbox(fn: () => void): () => void {
  listeners.add(fn);
  return () => {
    listeners.delete(fn);
  };
}

export function subscribeIdMap(fn: (from: number, to: number) => void): () => void {
  idListeners.add(fn);
  return () => {
    idListeners.delete(fn);
  };
}

export function loadOutbox(): OutboxItem[] {
  try {
    const raw = localStorage.getItem(KEY);
    return raw ? (JSON.parse(raw) as OutboxItem[]) : [];
  } catch {
    return [];
  }
}

function saveOutbox(items: OutboxItem[]) {
  localStorage.setItem(KEY, JSON.stringify(items));
  emit();
}

function loadMap(): Record<string, number> {
  try {
    const raw = localStorage.getItem(MAP_KEY);
    return raw ? (JSON.parse(raw) as Record<string, number>) : {};
  } catch {
    return {};
  }
}

function rememberId(from: number, to: number) {
  const map = loadMap();
  map[String(from)] = to;
  localStorage.setItem(MAP_KEY, JSON.stringify(map));
  idListeners.forEach((fn) => fn(from, to));
}

export function mappedId(id: number): number {
  return loadMap()[String(id)] ?? id;
}

export function pendingCount(): number {
  return loadOutbox().filter((item) => item.status === "pending" || item.status === "sending").length;
}

export function rejectedCount(): number {
  return loadOutbox().filter((item) => item.status === "rejected" || item.status === "failed").length;
}

export function useOutbox() {
  const [items, setItems] = useState(loadOutbox);
  useEffect(() => subscribeOutbox(() => setItems(loadOutbox())), []);
  return items;
}

function push(job: OutboxJob): OutboxItem {
  const item: OutboxItem = {
    id: crypto.randomUUID(),
    createdAt: Date.now(),
    status: "pending",
    job,
  };
  saveOutbox([...loadOutbox(), item]);
  return item;
}

function patchItem(id: string, patch: Partial<OutboxItem>) {
  saveOutbox(loadOutbox().map((item) => (item.id === id ? { ...item, ...patch } : item)));
}

function removeItem(id: string) {
  saveOutbox(loadOutbox().filter((item) => item.id !== id));
}

export function retryOutbox(id: string) {
  patchItem(id, { status: "pending", error: undefined });
}

export function dismissOutbox(id: string) {
  removeItem(id);
}

function remapPatient(from: number, to: number) {
  rememberId(from, to);
  saveOutbox(
    loadOutbox().map((item) => {
      if (item.job.type === "book-visit" || item.job.type === "book-surgery" || item.job.type === "note" || item.job.type === "exam") {
        if (item.job.patientId === from) {
          return { ...item, job: { ...item.job, patientId: to } };
        }
      }
      return item;
    }),
  );
}

export function enqueueCreatePatient(
  body: { name: string; national_code: string; mobile: string; age?: string },
): number {
  const localId = -Date.now();
  push({ type: "create-patient", localId, body });
  return localId;
}

export function enqueueVisit(patientId: number, body: BookVisitBody, slotLabel?: string): number {
  const localBookingId = -Date.now();
  push({ type: "book-visit", patientId: mappedId(patientId), localBookingId, body, slotLabel });
  return localBookingId;
}

export function enqueueSurgery(patientId: number, body: BookSurgeryBody, slotLabel?: string): number {
  const localBookingId = -Date.now() - 1;
  push({ type: "book-surgery", patientId: mappedId(patientId), localBookingId, body, slotLabel });
  return localBookingId;
}

export function enqueueNote(patientId: number, note: string): number {
  const localId = -Date.now();
  push({ type: "note", patientId: mappedId(patientId), localId, note });
  return localId;
}

export function enqueueExam(
  patientId: number,
  body: {
    history?: string;
    examination?: string;
    diagnosis?: string;
    treatment?: string;
    next_instruction?: string;
    eye_side?: string;
    va_right?: string;
    va_left?: string;
    iop_right?: string;
    iop_left?: string;
  },
): number {
  const localId = -Date.now();
  push({ type: "exam", patientId: mappedId(patientId), localId, body });
  return localId;
}

export function enqueueStatus(
  kind: string,
  bookingId: number,
  status: string,
  prevStatus?: string | null,
  prevLabel?: string | null,
) {
  push({ type: "status", kind, bookingId, status, prevStatus, prevLabel });
}

function liveJobs(): OutboxJob[] {
  return loadOutbox()
    .filter((item) => item.status === "pending" || item.status === "sending")
    .map((item) => item.job);
}

function syntheticPatient(job: Extract<OutboxJob, { type: "create-patient" }>): PatientDto {
  return {
    id: job.localId,
    name: job.body.name,
    national_code: job.body.national_code,
    mobile: job.body.mobile,
    age: job.body.age,
    initial: job.body.name.slice(0, 1),
  };
}

function visitBooking(job: Extract<OutboxJob, { type: "book-visit" }>): BookingDto {
  return {
    id: job.localBookingId,
    kind: "visit",
    patient_id: job.patientId,
    patient_name: job.body.patient_name,
    national_code: job.body.national_code,
    mobile: job.body.mobile,
    title: job.body.visit_type || "ویزیت",
    scheduled_date_jalali: job.body.scheduled_date,
    scheduled_time_label: job.slotLabel || job.body.scheduled_time,
    status: "scheduled",
    status_label: "در انتظار ارسال",
    locked: false,
    actions: [],
  };
}

function surgeryBooking(job: Extract<OutboxJob, { type: "book-surgery" }>): BookingDto {
  return {
    id: job.localBookingId,
    kind: "surgery",
    patient_id: job.patientId,
    patient_name: job.body.patient_name,
    national_code: job.body.national_code,
    mobile: job.body.mobile,
    title: job.body.surgery_type || "عمل",
    hospital_name: job.body.hospital_name,
    scheduled_date_jalali: job.body.scheduled_date,
    scheduled_time_label: job.slotLabel || job.body.scheduled_time,
    status: "scheduled",
    status_label: "در انتظار ارسال",
    locked: false,
    is_emergency: false,
    actions: [],
  };
}

function asTimeline(type: string, booking: BookingDto): TimelineItemDto {
  return {
    id: `local-${type}-${booking.id}`,
    type,
    payload: { ...booking },
  };
}

function applyStatus(items: BookingDto[] | undefined, jobs: OutboxJob[]): BookingDto[] {
  return (items || [])
    .filter((item) => item.id > 0)
    .map((item) => {
      const job = jobs.find(
        (entry) => entry.type === "status" && entry.bookingId === item.id && entry.kind === item.kind,
      );
      if (!job || job.type !== "status") return item;
      return {
        ...item,
        status: job.status,
        status_label: labels[job.status] || job.status,
      };
    });
}

export function overlayPatients(data: PatientsResponse): PatientsResponse {
  const extras = liveJobs()
    .filter((job): job is Extract<OutboxJob, { type: "create-patient" }> => job.type === "create-patient")
    .map(syntheticPatient);
  const existing = (data.patients || []).filter((item) => item.id > 0);
  const merged = [...extras.filter((p) => !existing.some((e) => e.id === p.id)), ...existing];
  return { ...data, patients: merged };
}

export function overlayHome(data: HomeResponse): HomeResponse {
  const jobs = liveJobs();
  const baseVisits = data.today_visits?.length ? data.today_visits : data.upcoming_visits || [];
  const baseSurgeries = data.today_surgeries?.length ? data.today_surgeries : data.upcoming_surgeries || [];
  const visits = [
    ...jobs.filter((j): j is Extract<OutboxJob, { type: "book-visit" }> => j.type === "book-visit").map(visitBooking),
    ...applyStatus(baseVisits, jobs),
  ];
  const surgeries = [
    ...jobs.filter((j): j is Extract<OutboxJob, { type: "book-surgery" }> => j.type === "book-surgery").map(surgeryBooking),
    ...applyStatus(baseSurgeries, jobs),
  ];
  const creates = jobs.filter((j): j is Extract<OutboxJob, { type: "create-patient" }> => j.type === "create-patient");
  const stats = (data.stats || []).map((stat) => {
    if (stat.key === "patients") return { ...stat, value: (stat.value || 0) + creates.length };
    if (stat.key === "visits_today") {
      const extra = jobs.filter((j) => j.type === "book-visit" && j.body.scheduled_date === data.today_jalali).length;
      return { ...stat, value: (stat.value || 0) + extra };
    }
    if (stat.key === "surgeries_today") {
      const extra = jobs.filter((j) => j.type === "book-surgery" && j.body.scheduled_date === data.today_jalali).length;
      return { ...stat, value: (stat.value || 0) + extra };
    }
    return stat;
  });
  return {
    ...data,
    stats,
    today_visits: visits,
    today_surgeries: surgeries,
  };
}

export function overlayBoard(data: BoardResponse): BoardResponse {
  const jobs = liveJobs();
  const extras =
    data.kind === "visit"
      ? jobs
          .filter((j): j is Extract<OutboxJob, { type: "book-visit" }> => j.type === "book-visit")
          .filter((j) => !data.date || j.body.scheduled_date === data.date)
          .map(visitBooking)
      : jobs
          .filter((j): j is Extract<OutboxJob, { type: "book-surgery" }> => j.type === "book-surgery")
          .filter((j) => !data.date || j.body.scheduled_date === data.date)
          .map(surgeryBooking);
  return {
    ...data,
    items: [...extras, ...applyStatus(data.items, jobs)],
  };
}

export function overlayPatient(id: number, data: PatientDetailResponse | null): PatientDetailResponse | null {
  const jobs = liveJobs();
  const create = jobs.find((j): j is Extract<OutboxJob, { type: "create-patient" }> => j.type === "create-patient" && j.localId === id);
  const patient = data?.patient || (create ? syntheticPatient(create) : null);
  if (!patient) return data;
  const extras: TimelineItemDto[] = [];
  for (const job of jobs) {
    if (job.type === "book-visit" && job.patientId === id) extras.push(asTimeline("appointment", visitBooking(job)));
    if (job.type === "book-surgery" && job.patientId === id) extras.push(asTimeline("surgery", surgeryBooking(job)));
    if (job.type === "note" && job.patientId === id) {
      extras.push({
        id: `local-note-${job.localId}`,
        type: "note",
        payload: { id: job.localId, note: job.note, creator_name: "شما", created_at_jalali: "در انتظار ارسال" },
      });
    }
    if (job.type === "exam" && job.patientId === id) {
      extras.push({
        id: `local-exam-${job.localId}`,
        type: "visit",
        payload: {
          id: job.localId,
          ...job.body,
          created_at_jalali: "در انتظار ارسال",
        },
      });
    }
  }
  const remote = (data?.timeline || []).filter((item) => !String(item.id || "").startsWith("local-"));
  const timeline = [...extras, ...remote].map((item) => {
    const payload = item.payload || {};
    const bookingId = Number(payload.id);
    const kind = item.type === "surgery" ? "surgery" : item.type === "appointment" ? "visit" : null;
    const job = jobs.find((j) => j.type === "status" && j.bookingId === bookingId && j.kind === kind);
    if (!job || job.type !== "status") return item;
    return {
      ...item,
      payload: { ...payload, status: job.status, status_label: labels[job.status] || job.status },
    };
  });
  return { ok: true, patient, timeline };
}

export async function flushOutbox(session: Session): Promise<void> {
  if (flushing) return;
  flushing = true;
  try {
    for (const item of loadOutbox()) {
      if (item.status !== "pending" && item.status !== "sending") continue;
      patchItem(item.id, { status: "sending", error: undefined });
      try {
        await sendJob(session, item.job);
        removeItem(item.id);
      } catch (error) {
        const message = error instanceof ApiError ? error.message : "ارسال انجام نشد.";
        if (isNetworkError(error) || (error instanceof ApiError && error.status >= 500)) {
          patchItem(item.id, { status: "pending", error: message });
          break;
        }
        const rejected = error instanceof ApiError && (error.status === 409 || error.status === 422);
        patchItem(item.id, { status: rejected ? "rejected" : "failed", error: message });
      }
    }
  } finally {
    flushing = false;
    emit();
  }
}

async function sendJob(session: Session, job: OutboxJob): Promise<void> {
  if (job.type === "create-patient") {
    try {
      const response = await clinicRequest<{ patient?: { id: number } }>(session, "POST", "patients", job.body);
      if (!response.patient?.id) throw new ApiError("ثبت بیمار پاسخ ناقص داشت.");
      remapPatient(job.localId, response.patient.id);
      return;
    } catch (error) {
      if (error instanceof ApiError && error.status === 422 && error.message.includes("ملی")) {
        const found = await clinicRequest<{ patients?: PatientDto[] }>(
          session,
          "GET",
          `patients?q=${encodeURIComponent(job.body.national_code)}`,
        );
        const match = found.patients?.find((p) => p.national_code === job.body.national_code);
        if (match) {
          remapPatient(job.localId, match.id);
          return;
        }
      }
      throw error;
    }
  }
  if (job.type === "book-visit") {
    const patientId = mappedId(job.patientId);
    if (patientId < 0) throw new ApiError("اول پرونده بیمار باید به هاست برسد.", 0);
    const body = {
      patient_name: job.body.patient_name,
      national_code: job.body.national_code,
      no_national_code: job.body.no_national_code,
      mobile: job.body.mobile,
      mobile_secondary: job.body.mobile_secondary,
      age: job.body.age,
      visit_type: job.body.visit_type,
      reason: job.body.reason,
      notes: job.body.notes,
      scheduled_date: job.body.scheduled_date,
      scheduled_time: job.body.scheduled_time,
    };
    if (patientId > 0) {
      await clinicRequest(session, "POST", `patients/${patientId}/visits`, body);
    } else {
      await clinicRequest(session, "POST", "visits", body);
    }
    return;
  }
  if (job.type === "book-surgery") {
    const patientId = mappedId(job.patientId);
    if (patientId < 0) throw new ApiError("اول پرونده بیمار باید به هاست برسد.", 0);
    const body = {
      patient_name: job.body.patient_name,
      national_code: job.body.national_code,
      no_national_code: job.body.no_national_code,
      mobile: job.body.mobile,
      mobile_secondary: job.body.mobile_secondary,
      age: job.body.age,
      hospital_id: job.body.hospital_id,
      surgery_type: job.body.surgery_type,
      surgery_type_id: job.body.surgery_type_id,
      surgery_subtype_id: job.body.surgery_subtype_id,
      eye_side: job.body.eye_side,
      scheduled_date: job.body.scheduled_date,
      scheduled_time: job.body.scheduled_time,
      surgeon_name: job.body.surgeon_name,
      notes: job.body.notes,
      is_emergency: job.body.is_emergency,
      is_exception: job.body.is_exception,
    };
    if (patientId > 0) {
      await clinicRequest(session, "POST", `patients/${patientId}/surgeries`, body);
    } else {
      await clinicRequest(session, "POST", "surgeries", body);
    }
    return;
  }
  if (job.type === "note") {
    const patientId = mappedId(job.patientId);
    if (patientId < 0) throw new ApiError("اول پرونده بیمار باید به هاست برسد.", 0);
    await clinicRequest(session, "POST", `patients/${patientId}/notes`, { note: job.note });
    return;
  }
  if (job.type === "exam") {
    const patientId = mappedId(job.patientId);
    if (patientId < 0) throw new ApiError("اول پرونده بیمار باید به هاست برسد.", 0);
    await clinicRequest(session, "POST", `patients/${patientId}/exams`, job.body);
    return;
  }
  const path =
    job.kind === "surgery" ? `surgery-appointments/${job.bookingId}/status` : `appointments/${job.bookingId}/status`;
  await clinicRequest(session, "PATCH", path, { status: job.status });
}
