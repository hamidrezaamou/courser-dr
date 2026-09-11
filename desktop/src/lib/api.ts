import { cacheGet, cacheSet } from "./cache";
import { ApiError, clinicRequest } from "./client";
import { overlayBoard, overlayHome, overlayPatient, overlayPatients } from "./outbox";
import { digitsOnly, normalizeSite } from "./session";
import type {
  BoardResponse,
  FloorResponse,
  FollowupsResponse,
  HomeResponse,
  HospitalDto,
  LoginResponse,
  PanelBootstrap,
  PatientDetailResponse,
  PatientsResponse,
  ReportsResponse,
  Session,
  SlotsResponse,
  SurgeryCatalogResponse,
  VisitCalendarResponse,
  BookingDto,
  BookVisitBody,
  BookSurgeryBody,
  UserDto,
} from "./types";

export { ApiError } from "./client";

async function cached<T>(
  key: string,
  loader: () => Promise<T>,
  overlay?: (data: T) => T,
): Promise<{ data: T; fromCache: boolean }> {
  const wrap = (data: T, fromCache: boolean) => ({
    data: overlay ? overlay(data) : data,
    fromCache,
  });
  try {
    const fresh = await loader();
    cacheSet(key, fresh);
    return wrap(fresh, false);
  } catch (error) {
    const cachedValue = cacheGet<T>(key);
    if (cachedValue) return wrap(cachedValue, true);
    throw error;
  }
}

export async function login(
  site: string,
  nationalCode: string,
  password: string,
): Promise<Session> {
  const response = await clinicRequest<LoginResponse>(
    { site, token: "" },
    "POST",
    "login",
    {
      national_code: digitsOnly(nationalCode),
      password,
      device_name: "windows-desktop",
    },
  );
  if (!response.token || !response.user) {
    throw new ApiError(response.message || "ورود ناموفق بود.");
  }
  return {
    token: response.token,
    site: normalizeSite(site),
    user: response.user,
    clinic: response.clinic,
  };
}

export async function logout(session: Session): Promise<void> {
  try {
    await clinicRequest(session, "POST", "logout");
  } catch {
    /* still clear locally */
  }
}

export async function loadHome(session: Session) {
  return cached("home", () => clinicRequest<HomeResponse>(session, "GET", "home"), overlayHome);
}

export async function loadPatients(session: Session, q: string, page = 1) {
  const query = new URLSearchParams();
  if (q.trim()) query.set("q", q.trim());
  query.set("page", String(page));
  const key = `patients:${q.trim()}:${page}`;
  return cached(
    key,
    () => clinicRequest<PatientsResponse>(session, "GET", `patients?${query.toString()}`),
    overlayPatients,
  );
}

export async function loadPatient(session: Session, id: number) {
  if (id < 0) {
    const local = overlayPatient(id, cacheGet(`patient:${id}`));
    if (local?.patient) return { data: local, fromCache: true };
  }
  try {
    const fresh = await clinicRequest<PatientDetailResponse>(session, "GET", `patients/${id}`);
    cacheSet(`patient:${id}`, fresh);
    return { data: overlayPatient(id, fresh) || fresh, fromCache: false };
  } catch (error) {
    const cachedValue = cacheGet<PatientDetailResponse>(`patient:${id}`);
    const overlaid = overlayPatient(id, cachedValue);
    if (overlaid?.patient) return { data: overlaid, fromCache: true };
    throw error;
  }
}

export async function loadBoard(session: Session, kind: "visit" | "surgery", date?: string | null, hospitalId?: number | null) {
  const query = new URLSearchParams({ kind });
  if (date) query.set("date", date);
  if (hospitalId) query.set("hospital_id", String(hospitalId));
  const key = `board:${kind}:${date || "default"}:${hospitalId || "all"}`;
  return cached(
    key,
    () => clinicRequest<BoardResponse>(session, "GET", `board?${query.toString()}`),
    overlayBoard,
  );
}

export async function loadVisitCalendar(session: Session) {
  return cached("visit-calendar", () => clinicRequest<VisitCalendarResponse>(session, "GET", "visit-calendar"));
}

export async function loadSlots(
  session: Session,
  date: string,
  kind: "visit" | "surgery",
  extra?: { hospitalId?: number | null; typeId?: number | null; subtypeId?: number | null; excludeId?: number | null },
) {
  const query = new URLSearchParams({ date, kind });
  if (extra?.hospitalId) query.set("hospital_id", String(extra.hospitalId));
  if (extra?.typeId) query.set("surgery_type_id", String(extra.typeId));
  if (extra?.subtypeId) query.set("surgery_subtype_id", String(extra.subtypeId));
  if (extra?.excludeId) query.set("exclude_id", String(extra.excludeId));
  const key = `slots:${kind}:${date}:${extra?.hospitalId || ""}:${extra?.typeId || ""}:${extra?.subtypeId || ""}:${extra?.excludeId || ""}`;
  return cached(key, () => clinicRequest<SlotsResponse>(session, "GET", `slots?${query.toString()}`));
}

export async function loadHospitals(session: Session) {
  return cached("hospitals", async () => {
    const response = await clinicRequest<{ hospitals?: HospitalDto[] }>(session, "GET", "hospitals");
    return response.hospitals || [];
  });
}

export async function loadSurgeryOptions(
  session: Session,
  hospitalId: number,
  typeId?: number | null,
  subtypeId?: number | null,
) {
  const query = new URLSearchParams({ hospital_id: String(hospitalId) });
  if (typeId) query.set("surgery_type_id", String(typeId));
  if (subtypeId) query.set("surgery_subtype_id", String(subtypeId));
  const key = `surgery-options:${hospitalId}:${typeId || ""}:${subtypeId || ""}`;
  return cached(key, () =>
    clinicRequest<SurgeryCatalogResponse>(session, "GET", `surgery-options?${query.toString()}`),
  );
}

export async function prefetchCatalogs(session: Session): Promise<void> {
  await Promise.allSettled([
    loadVisitCalendar(session),
    loadHospitals(session),
    loadHome(session),
    loadPanel(session),
  ]);
  try {
    const hospitals = await loadHospitals(session);
    const first = hospitals.data[0];
    if (!first) return;
    const catalog = await loadSurgeryOptions(session, first.id);
    const typeId = Number(catalog.data.types?.[0]?.id);
    if (typeId) await loadSurgeryOptions(session, first.id, typeId);
  } catch {
    /* optional warmup */
  }
}

export async function loadPanel(session: Session) {
  return cached("panel", () => clinicRequest<PanelBootstrap>(session, "GET", "panel"));
}

export async function loadReports(
  session: Session,
  kind: string,
  from: string,
  to: string,
  q = "",
  status = "",
  hospitalId?: number | null,
  extra?: { emergency?: boolean; surgeryTypeId?: number | null; surgerySubtypeId?: string },
) {
  const query = new URLSearchParams({ kind, from, to });
  if (q.trim()) query.set("q", q.trim());
  if (status) query.set("status", status);
  if (hospitalId) query.set("hospital_id", String(hospitalId));
  if (extra?.emergency) query.set("emergency", "1");
  if (extra?.surgeryTypeId) query.set("surgery_type_id", String(extra.surgeryTypeId));
  if (extra?.surgerySubtypeId) query.set("surgery_subtype_id", extra.surgerySubtypeId);
  const key = `reports:${kind}:${from}:${to}:${q.trim()}:${status}:${hospitalId || ""}:${extra?.emergency ? 1 : 0}:${extra?.surgeryTypeId || ""}:${extra?.surgerySubtypeId || ""}`;
  try {
    return await cached(key, () => clinicRequest<ReportsResponse>(session, "GET", `reports?${query.toString()}`));
  } catch (error) {
    if (!(error instanceof ApiError) || (error.status !== 404 && error.status !== 403 && error.status !== 0 && error.status < 500)) {
      throw error;
    }
    const emptyBoard = { data: { items: [] } as BoardResponse, fromCache: false };
    const [visits, surgeries] = await Promise.all([
      kind === "surgery" ? Promise.resolve(emptyBoard) : loadBoard(session, "visit", from),
      kind === "visit" ? Promise.resolve(emptyBoard) : loadBoard(session, "surgery", from),
    ]);
    const rows = [...(visits.data.items || []), ...(surgeries.data.items || [])];
    const data: ReportsResponse = {
      kind,
      from,
      to,
      today: visits.data.today || surgeries.data.today || undefined,
      tomorrow: visits.data.tomorrow || surgeries.data.tomorrow || undefined,
      rows: q.trim()
        ? rows.filter((row) => `${row.patient_name || ""} ${row.national_code || ""} ${row.mobile || ""}`.includes(q.trim()))
        : rows,
    };
    cacheSet(key, data);
    return { data, fromCache: visits.fromCache || surgeries.fromCache };
  }
}

export async function loadFloor(session: Session, kind: string, date?: string | null) {
  const query = new URLSearchParams({ kind });
  if (date) query.set("date", date);
  const key = `floor:${kind}:${date || "default"}`;
  try {
    return await cached(key, () => clinicRequest<FloorResponse>(session, "GET", `floor?${query.toString()}`));
  } catch (error) {
    if (!(error instanceof ApiError) || (error.status !== 404 && error.status !== 403 && error.status !== 0 && error.status < 500)) {
      throw error;
    }
    const emptyBoard = { data: { items: [] } as BoardResponse, fromCache: false };
    const [visits, surgeries] = await Promise.all([
      kind === "surgery" ? Promise.resolve(emptyBoard) : loadBoard(session, "visit", date),
      kind === "visit" ? Promise.resolve(emptyBoard) : loadBoard(session, "surgery", date),
    ]);
    const items = [...(visits.data.items || []), ...(surgeries.data.items || [])];
    const group = (statuses: string[]) => items.filter((item) => statuses.includes(item.status || ""));
    const data: FloorResponse = {
      date: visits.data.date || surgeries.data.date || date || undefined,
      today: visits.data.today || surgeries.data.today || undefined,
      kind,
      upcoming: group(["scheduled", "confirmed"]),
      waiting: group(["waiting"]),
      ready: group(["ready"]),
      in_consult: group(["in_consult"]),
    };
    cacheSet(key, data);
    return { data, fromCache: visits.fromCache || surgeries.fromCache };
  }
}

export async function loadFollowups(session: Session, bucket: string, filters: Record<string, string> = {}) {
  const query = new URLSearchParams({ bucket });
  for (const [key, value] of Object.entries(filters)) {
    if (value) query.set(key, value);
  }
  const key = `followups:${query.toString()}`;
  try {
    return await cached(key, () => clinicRequest<FollowupsResponse>(session, "GET", `followups?${query.toString()}`));
  } catch {
    const home = await loadHome(session);
    const items = [
      ...(home.data.today_visits || []).map((row) => ({
        id: row.id,
        title: row.title || "ویزیت",
        status_label: row.status_label,
        due_jalali: row.scheduled_date_jalali,
        patient_id: row.patient_id,
        patient_name: row.patient_name,
      })),
      ...(home.data.today_surgeries || []).map((row) => ({
        id: row.id + 100000,
        title: row.title || "عمل",
        status_label: row.status_label,
        due_jalali: row.scheduled_date_jalali,
        patient_id: row.patient_id,
        patient_name: row.patient_name,
      })),
    ];
    const data: FollowupsResponse = { available: false, bucket, counts: { today: items.length }, items };
    cacheSet(key, data);
    return { data, fromCache: home.fromCache };
  }
}

export async function updatePatient(
  session: Session,
  id: number,
  body: { name: string; national_code: string; mobile: string; mobile_secondary?: string; age?: string },
) {
  return clinicRequest<{ patient?: { id: number } }>(session, "PATCH", `patients/${id}`, body);
}

export async function loadAppointment(session: Session, id: number) {
  return clinicRequest<{ appointment?: BookingDto }>(session, "GET", `appointments/${id}`);
}

export async function loadSurgery(session: Session, id: number) {
  return clinicRequest<{ surgery?: BookingDto }>(session, "GET", `surgery-appointments/${id}`);
}

export async function updateVisit(session: Session, id: number, body: BookVisitBody) {
  return clinicRequest<{ appointment?: BookingDto; message?: string }>(session, "PUT", `appointments/${id}`, body);
}

export async function updateSurgery(session: Session, id: number, body: BookSurgeryBody) {
  return clinicRequest<{ surgery?: BookingDto; message?: string }>(session, "PUT", `surgery-appointments/${id}`, body);
}

export async function checkCooldown(
  session: Session,
  params: {
    patientId?: number | null;
    date?: string;
    surgeryTypeId?: number | null;
    surgerySubtypeId?: number | null;
    eyeSide?: string;
    excludeId?: number | null;
    nationalCode?: string;
  },
) {
  const query = new URLSearchParams();
  if (params.patientId) query.set("patient_id", String(params.patientId));
  if (params.date) query.set("date", params.date);
  if (params.surgeryTypeId) query.set("surgery_type_id", String(params.surgeryTypeId));
  if (params.surgerySubtypeId) query.set("surgery_subtype_id", String(params.surgerySubtypeId));
  if (params.eyeSide) query.set("eye_side", params.eyeSide);
  if (params.excludeId) query.set("exclude_id", String(params.excludeId));
  if (params.nationalCode) query.set("national_code", params.nationalCode);
  return clinicRequest<{
    conflict?: boolean;
    message?: string;
    confirm_message?: string;
    days?: number;
    scope?: string;
    previous?: {
      date?: string | null;
      time?: string | null;
      hospital?: string | null;
      type?: string | null;
      subtype?: string | null;
      eye?: string | null;
      status?: string | null;
      when?: string | null;
      scope?: string | null;
    } | null;
  }>(
    session,
    "GET",
    `surgeries/cooldown-check?${query.toString()}`,
  );
}

export async function updateProfile(session: Session, body: { name: string; mobile?: string; password?: string; password_confirmation?: string }) {
  return clinicRequest<{ user?: UserDto; message?: string }>(session, "PATCH", "me", body);
}
