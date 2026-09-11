import { clinicDownload, clinicRequest } from "./client";
import type { BookingDto, BookingPrefill, HospitalDto, Session } from "./types";

export type DrugDto = {
  id: number;
  name?: string | null;
  generic_name?: string | null;
  dosage_form?: string | null;
  default_dosage?: string | null;
  default_frequency?: string | null;
  default_duration?: string | null;
  default_instructions?: string | null;
  is_active?: boolean;
};

export type FeatureFlagDto = { key: string; label: string; hint?: string; enabled: boolean };
export type StaffUserDto = { id: number; name?: string | null; national_code?: string | null; mobile?: string | null; role?: string | null };
export type WaitingDto = {
  id: number;
  patient_id?: number | null;
  patient_name?: string | null;
  mobile?: string | null;
  national_code?: string | null;
  kind?: string | null;
  status?: string | null;
  priority?: number;
  notes?: string | null;
  preferred_jalali?: string | null;
};
export type LedgerRow = {
  id: number;
  patient_id?: number | null;
  patient_name?: string | null;
  type?: string | null;
  amount?: number;
  method?: string | null;
  label?: string | null;
  notes?: string | null;
};
export type MessageDto = {
  id: number;
  patient_id?: number | null;
  patient_name?: string | null;
  actor_name?: string | null;
  read?: boolean;
  created_at_jalali?: string | null;
};
export type ReportNoteDto = {
  id: number;
  body?: string;
  author?: string;
  author_name?: string;
  include_in_print?: boolean;
  mine?: boolean;
};

export async function loadCatalog(session: Session) {
  return clinicRequest<{
    drugs?: DrugDto[];
    hospitals?: HospitalDto[];
    surgery_types?: Array<{ id: number; name?: string; is_active?: boolean; cooldown_days?: number | null; subtypes?: Array<{ id: number; name?: string; cooldown_days?: number | null }> }>;
    features?: FeatureFlagDto[];
    quick_links?: Array<{ title: string; url: string }>;
  }>(session, "GET", "catalog");
}

export async function uploadDocuments(session: Session, patientId: number, files: FileList, type: string, description = "") {
  const form = new FormData();
  form.append("type", type);
  if (description) form.append("description", description);
  Array.from(files).forEach((file) => form.append("files[]", file));
  return clinicRequest(session, "POST", `patients/${patientId}/documents`, form);
}

export async function storePrescription(
  session: Session,
  patientId: number,
  body: { notes?: string; items: Array<{ drug_id?: number; drug_name: string; dosage?: string; frequency?: string; duration?: string; instructions?: string }> },
) {
  return clinicRequest(session, "POST", `patients/${patientId}/prescriptions`, body);
}

export async function storeDrawing(session: Session, patientId: number, image: string) {
  return clinicRequest(session, "POST", "drawings", { patient_id: patientId, image });
}

export async function followupAction(session: Session, id: number, action: string, extra: Record<string, unknown> = {}) {
  return clinicRequest(session, "POST", `followups/${id}/action`, { action, ...extra });
}

export async function storeFollowup(session: Session, body: { patient_id: number; title: string; due_date: string; notes?: string; kind?: string; method?: string }) {
  return clinicRequest(session, "POST", "followups", body);
}

export async function loadReportNotes(session: Session, subjectType: string, subjectId: number) {
  const data = await clinicRequest<{ notes?: ReportNoteDto[]; messages?: ReportNoteDto[] }>(
    session,
    "GET",
    `report-notes?subject_type=${subjectType}&subject_id=${subjectId}`,
  );
  return { notes: data.notes || data.messages || [] };
}

export async function storeReportNote(session: Session, body: { subject_type: string; subject_id: number; body: string }) {
  return clinicRequest(session, "POST", "report-notes", body);
}

export async function updateReportNote(session: Session, id: number, body: string) {
  return clinicRequest(session, "PUT", `report-notes/${id}`, { body });
}

export async function destroyReportNote(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `report-notes/${id}`);
}

export async function toggleReportNotePrint(session: Session, id: number, includeInPrint?: boolean) {
  return clinicRequest(session, "PATCH", `report-notes/${id}/print`, includeInPrint == null ? {} : { include_in_print: includeInPrint });
}

export async function loadMessages(session: Session, filter = "unread") {
  return clinicRequest<{ unread?: number; items?: MessageDto[] }>(session, "GET", `messages?filter=${filter}`);
}

export async function markMessagesRead(session: Session) {
  return clinicRequest(session, "POST", "messages/read-all");
}

export async function storeHospital(session: Session, body: { name: string; address?: string; phone?: string }) {
  return clinicRequest(session, "POST", "hospitals", body);
}

export async function updateHospital(session: Session, id: number, body: { name: string; address?: string; phone?: string }) {
  return clinicRequest(session, "PATCH", `hospitals/${id}`, body);
}

export async function destroyHospital(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `hospitals/${id}`);
}

export async function storeDrug(session: Session, body: {
  name: string;
  generic_name?: string;
  dosage_form?: string;
  default_dosage?: string;
  default_frequency?: string;
  default_duration?: string;
  default_instructions?: string;
}) {
  return clinicRequest(session, "POST", "drugs", body);
}

export async function updateDrug(session: Session, id: number, body: {
  name: string;
  generic_name?: string;
  dosage_form?: string;
  default_dosage?: string;
  default_frequency?: string;
  default_duration?: string;
  default_instructions?: string;
  is_active?: boolean;
}) {
  return clinicRequest(session, "PATCH", `drugs/${id}`, body);
}

export async function destroyDrug(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `drugs/${id}`);
}

export async function storeSurgeryType(session: Session, name: string) {
  return clinicRequest(session, "POST", "surgery-types", { name });
}

export type TimeDayDto = {
  id: number;
  kind?: string;
  date_key?: string;
  display_text?: string | null;
  year?: number | null;
  month?: number | null;
  day?: number | null;
  weekday?: string | null;
  hospital_id?: number | null;
  hospital_name?: string | null;
  surgery_type_id?: number | null;
  surgery_type_name?: string | null;
  surgery_subtype_name?: string | null;
  total_slots?: number;
  times_count?: number;
  sms_text?: string;
};

export async function storeTime(session: Session, body: {
  kind: string;
  date_key: string;
  hospital_id?: number;
  surgery_type_id?: number;
  total_slots?: number;
  times?: string;
  sms_text?: string;
  slot_mode?: "time" | "queue";
}) {
  return clinicRequest(session, "POST", "times", body);
}

export async function loadTimes(session: Session, kind: string, extra?: { hospitalId?: number | null; typeId?: number | null }) {
  const query = new URLSearchParams({ kind });
  if (extra?.hospitalId) query.set("hospital_id", String(extra.hospitalId));
  if (extra?.typeId) query.set("surgery_type_id", String(extra.typeId));
  return clinicRequest<{
    kind?: string;
    default_sms?: string;
    items?: TimeDayDto[];
  }>(session, "GET", `times?${query.toString()}`);
}

export async function updateTimeSms(session: Session, id: number, smsText: string) {
  return clinicRequest<{ message?: string }>(session, "PATCH", `times/${id}/sms`, { sms_text: smsText });
}

export async function loadUsers(session: Session) {
  return clinicRequest<{ users?: StaffUserDto[] }>(session, "GET", "users");
}

export async function storeUser(session: Session, body: { name: string; national_code: string; mobile?: string; role: string; password: string }) {
  return clinicRequest(session, "POST", "users", body);
}

export async function updateUser(session: Session, id: number, body: Record<string, unknown>) {
  return clinicRequest(session, "PATCH", `users/${id}`, body);
}

export async function destroyUser(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `users/${id}`);
}

export async function updateFeatures(session: Session, flags: Record<string, boolean>) {
  return clinicRequest(session, "PUT", "features", { flags });
}

export async function updateQuickLinks(session: Session, links: Array<{ title: string; url: string }>) {
  return clinicRequest(session, "PUT", "quick-links", { links });
}

export async function loadWaiting(session: Session) {
  return clinicRequest<{ entries?: WaitingDto[] }>(session, "GET", "waiting");
}

export async function storeWaiting(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "waiting", body);
}

export async function waitingStatus(session: Session, id: number, status: string) {
  return clinicRequest(session, "PATCH", `waiting/${id}`, { status });
}

export async function convertWaiting(session: Session, id: number) {
  return clinicRequest<{
    ok?: boolean;
    kind?: "visit" | "surgery" | string;
    created?: boolean;
    appointment_id?: number;
    patient_id?: number | null;
    prefill?: BookingPrefill;
    message?: string;
  }>(session, "POST", `waiting/${id}/convert`);
}

export async function loadAccounting(session: Session, date: string) {
  return clinicRequest<{ date?: string; income?: number; charges?: number; rows?: LedgerRow[] }>(session, "GET", `accounting?date=${encodeURIComponent(date)}`);
}

export async function storeAccounting(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "accounting", body);
}

export async function loadApprovals(session: Session) {
  return clinicRequest<{ pending?: BookingDto[] }>(session, "GET", "approvals");
}

export async function approveBooking(session: Session, id: number) {
  return clinicRequest(session, "POST", `approvals/${id}/approve`);
}

export async function rejectBooking(session: Session, id: number) {
  return clinicRequest(session, "POST", `approvals/${id}/reject`);
}

export async function loadPrints(session: Session, date: string) {
  return clinicRequest<{ date?: string; types?: Record<string, string>; items?: BookingDto[] }>(session, "GET", `prints?date=${encodeURIComponent(date)}`);
}

export async function loadPrintSheet(session: Session, surgeryId: number, type: string) {
  return clinicRequest<{ html?: string; title?: string }>(session, "GET", `prints/${surgeryId}?type=${encodeURIComponent(type)}`);
}

export async function loadPrintHub(session: Session, surgeryId: number) {
  return clinicRequest<{
    surgery?: BookingDto;
    types?: Record<string, string>;
    checklist_warning?: string | null;
  }>(session, "GET", `prints/${surgeryId}/hub`);
}

export async function loadPrintAll(session: Session, surgeryId: number) {
  return clinicRequest<{ html?: string; title?: string }>(session, "GET", `prints/${surgeryId}/all`);
}

export async function loadRxPrint(session: Session, id: number) {
  return clinicRequest<{ html?: string }>(session, "GET", `prescriptions/${id}/print`);
}

export function openPrintHtml(html: string) {
  const win = window.open("", "_blank", "noopener,noreferrer");
  if (!win) return;
  win.document.write(html);
  win.document.close();
  win.focus();
  setTimeout(() => win.print(), 250);
}

export async function rotateDocument(session: Session, patientId: number, documentId: number, direction = "cw") {
  return clinicRequest(session, "POST", `patients/${patientId}/documents/${documentId}/rotate`, { direction });
}

export async function destroyDocument(session: Session, patientId: number, documentId: number) {
  return clinicRequest(session, "DELETE", `patients/${patientId}/documents/${documentId}`);
}

export async function storeVoice(session: Session, patientId: number, audio: Blob) {
  const form = new FormData();
  form.append("patient_id", String(patientId));
  form.append("audio", audio, "voice.webm");
  return clinicRequest(session, "POST", "voice", form);
}

export async function uploadPatientPhoto(session: Session, patientId: number, file: File) {
  const form = new FormData();
  form.append("photo", file);
  return clinicRequest(session, "POST", `patients/${patientId}/photo`, form);
}

export async function sendReminders(session: Session, date?: string) {
  return clinicRequest<{ message?: string }>(session, "POST", "reminders/send", date ? { date } : {});
}

export async function toggleSms(session: Session) {
  return clinicRequest<{ enabled?: boolean; message?: string }>(session, "PUT", "reminders/sms");
}

export async function destroyTime(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `times/${id}`);
}

export async function destroySurgeryType(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `surgery-types/${id}`);
}

export async function storeSubtype(session: Session, typeId: number, name: string) {
  return clinicRequest(session, "POST", `surgery-types/${typeId}/subtypes`, { name });
}

export async function loadBilling(session: Session) {
  return clinicRequest<{
    tariffs?: Array<{ id: number; name: string; kind: string; amount: number; insurance_coverage: number }>;
    records?: Array<{ id: number; patient_id?: number; patient_name?: string; fee_amount?: number; insurance_share?: number; patient_share?: number; settlement_status?: string; tariff?: string }>;
    recent_visits?: Array<{ id: number; patient_id?: number; patient_name?: string }>;
    recent_surgeries?: Array<{ id: number; patient_id?: number; patient_name?: string }>;
  }>(session, "GET", "billing");
}

export async function storeTariff(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "billing/tariffs", body);
}

export async function storeBilling(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "billing/records", body);
}

export async function loadConsent(session: Session) {
  return clinicRequest<{
    templates?: Array<{ id: number; title: string; kind: string; body?: string }>;
    recent?: Array<{ id: number; patient_id?: number; patient_name?: string; template?: string; signed_by_name?: string }>;
  }>(session, "GET", "consent");
}

export async function storeConsentTemplate(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "consent/templates", body);
}

export async function storeConsent(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "consent/records", body);
}

export async function loadPortal(session: Session) {
  return clinicRequest<{ portal_patients?: number; total_patients?: number }>(session, "GET", "portal");
}

export async function loadQuality(session: Session) {
  return clinicRequest<{ month?: string; stats?: Record<string, number> }>(session, "GET", "quality");
}

export async function loadEyeChart(session: Session) {
  return clinicRequest<{
    visits?: Array<{ id: number; patient_id?: number; patient_name?: string; va_right?: string; va_left?: string; iop_right?: string; iop_left?: string; created_at_jalali?: string }>;
  }>(session, "GET", "eye-chart");
}

export async function loadRxPrints(session: Session) {
  return clinicRequest<{
    prescriptions?: Array<{ id: number; patient_id?: number; patient_name?: string; created_at_jalali?: string; items?: Array<{ drug_name?: string; dosage?: string }> }>;
  }>(session, "GET", "rx-prints");
}

export async function loadContacts(session: Session) {
  return clinicRequest<{ contacts?: Array<{ name?: string; phones?: string[] }> }>(session, "GET", "contacts");
}

export async function downloadContactsVcf(session: Session) {
  return clinicDownload(session, "contacts/vcf", `contacts-${new Date().toISOString().slice(0, 10)}.vcf`);
}

export async function loadManagePatients(session: Session, q = "") {
  return clinicRequest<{ patients?: Array<{ id: number; name?: string; national_code?: string; mobile?: string }> }>(
    session,
    "GET",
    `patients-manage?q=${encodeURIComponent(q)}`,
  );
}

export async function secureErase(session: Session, id: number, confirmName: string) {
  return clinicRequest(session, "POST", `patients/${id}/secure-erase`, { confirm_name: confirmName });
}

export async function loadProgramGroups(session: Session) {
  return clinicRequest<{ groups?: Array<{ id: number; name: string; is_active?: boolean }> }>(session, "GET", "program-groups");
}

export async function storeProgramGroup(session: Session, name: string) {
  return clinicRequest(session, "POST", "program-groups", { name });
}

export type FollowupCatalogRow = { id: number; slug: string; label: string; sort_order?: number; is_active?: boolean };
export type FollowupStepDto = {
  id: number;
  title?: string;
  kind?: string;
  method?: string;
  offset_amount?: number;
  offset_unit?: string;
  offset_direction?: string;
  reference_event?: string;
  assigned_user_id?: number | null;
  sort_order?: number;
  timing_label?: string;
};
export type FollowupTemplateDto = {
  id: number;
  name: string;
  description?: string | null;
  applies_to?: string;
  is_active?: boolean;
  hospital_id?: number | null;
  surgery_type_id?: number | null;
  surgery_subtype_id?: number | null;
  binding_label?: string;
  steps?: FollowupStepDto[];
};

export async function loadFollowupSettings(session: Session) {
  return clinicRequest<{
    can_manage?: boolean;
    templates?: FollowupTemplateDto[];
    kinds?: FollowupCatalogRow[];
    methods?: FollowupCatalogRow[];
    outcomes?: FollowupCatalogRow[];
    hospitals?: Array<{ id: number; name?: string }>;
    surgery_types?: Array<{ id: number; name?: string; subtypes?: Array<{ id: number; name?: string }> }>;
    staff?: Array<{ id: number; name?: string }>;
    units?: Array<{ slug: string; label: string }>;
    directions?: Array<{ slug: string; label: string }>;
    reference_events?: Array<{ slug: string; label: string }>;
  }>(session, "GET", "followup-settings");
}

export async function storeFollowupCatalog(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "followup-settings/catalog", body);
}

export async function updateFollowupCatalog(session: Session, id: number, body: Record<string, unknown>) {
  return clinicRequest(session, "PATCH", `followup-settings/catalog/${id}`, body);
}

export async function storeFollowupTemplate(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", "followup-settings/templates", body);
}

export async function updateFollowupTemplate(session: Session, id: number, body: Record<string, unknown>) {
  return clinicRequest(session, "PATCH", `followup-settings/templates/${id}`, body);
}

export async function destroyFollowupTemplate(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `followup-settings/templates/${id}`);
}

export async function storeFollowupStep(session: Session, templateId: number, body: Record<string, unknown>) {
  return clinicRequest(session, "POST", `followup-settings/templates/${templateId}/steps`, body);
}

export async function updateFollowupStep(session: Session, id: number, body: Record<string, unknown>) {
  return clinicRequest(session, "PATCH", `followup-settings/steps/${id}`, body);
}

export async function destroyFollowupStep(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `followup-settings/steps/${id}`);
}

export async function loadActivityLogs(session: Session) {
  return clinicRequest<{ logs?: Array<{ id: number; action?: string; user?: string; subject?: string; jalali?: string }> }>(session, "GET", "activity-logs");
}

export async function loadCommunications(session: Session) {
  return clinicRequest<{ reminders_enabled?: boolean; sms_enabled?: boolean; days_ahead?: number; send_time?: string; visit_sms_on_booking?: boolean }>(session, "GET", "communications");
}

export async function updateCommunications(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "PUT", "communications", body);
}

export async function loadBrand(session: Session) {
  return clinicRequest<{ doctor_name?: string; phone?: string }>(session, "GET", "brand");
}

export async function updateBrand(session: Session, body: { doctor_name: string; phone?: string }) {
  return clinicRequest(session, "PUT", "brand", body);
}

export async function loadHis(session: Session) {
  return clinicRequest<{ rows?: Array<{ resource?: string; imported?: number; updated_at?: string }> }>(session, "GET", "his");
}

export type ReadyAnswerDto = {
  id: number;
  title?: string | null;
  category?: string | null;
  body?: string;
  is_pinned?: boolean;
  usage_count?: number;
};

export type ReadyChip = { token: string; label: string };

export async function loadReadyAnswers(session: Session, q = "", category = "", sort = "smart") {
  const query = `q=${encodeURIComponent(q)}&category=${encodeURIComponent(category)}&sort=${encodeURIComponent(sort)}`;
  return clinicRequest<{
    items?: ReadyAnswerDto[];
    categories?: string[];
    chips?: { person?: ReadyChip[]; booking?: ReadyChip[] };
    sms_enabled?: boolean;
  }>(session, "GET", `ready-answers?${query}`);
}

export async function storeReadyAnswer(session: Session, body: { title?: string; category?: string; body: string; is_pinned?: boolean }) {
  return clinicRequest(session, "POST", "ready-answers", body);
}

export async function updateReadyAnswer(session: Session, id: number, body: Record<string, unknown>) {
  return clinicRequest(session, "PUT", `ready-answers/${id}`, body);
}

export async function destroyReadyAnswer(session: Session, id: number) {
  return clinicRequest(session, "DELETE", `ready-answers/${id}`);
}

export async function pinReadyAnswer(session: Session, id: number) {
  return clinicRequest(session, "POST", `ready-answers/${id}/pin`);
}

export async function duplicateReadyAnswer(session: Session, id: number) {
  return clinicRequest(session, "POST", `ready-answers/${id}/duplicate`);
}

export async function markReadyAnswerUsed(session: Session, id: number) {
  return clinicRequest(session, "POST", `ready-answers/${id}/used`);
}

export async function sendReadyAnswer(session: Session, body: { mobile: string; message: string; answer_id?: number }) {
  return clinicRequest<{ message?: string }>(session, "POST", "ready-answers/send", body);
}

export async function loadReadyBookings(session: Session, patientId: number) {
  return clinicRequest<{ data?: Array<{ kind?: string; id: number; label?: string; meta?: string; vars?: Record<string, string> }> }>(
    session,
    "GET",
    `ready-answers/bookings/${patientId}`,
  );
}

export function applyMessageTags(body: string, vars: Record<string, string>) {
  return body.replace(/\{([^}]+)\}/g, (full, key: string) => (key in vars ? vars[key] : full));
}

export type ChecklistItemDto = {
  id: number;
  label?: string;
  checked?: boolean;
  checked_stamp?: string | null;
  checked_by_me?: boolean;
  is_custom?: boolean;
};

export type ChecklistDto = {
  id: number;
  title?: string;
  saved_at_jalali?: string | null;
  unchecked_count?: number;
  items?: ChecklistItemDto[];
};

export async function loadChecklistTemplate(session: Session, typeId: number, subtypeId?: number | null) {
  const extra = subtypeId ? `&surgery_subtype_id=${subtypeId}` : "";
  return clinicRequest<{ items?: Array<{ id: number; label: string }> }>(session, "GET", `checklist-template?surgery_type_id=${typeId}${extra}`);
}

export async function updateChecklistTemplate(session: Session, body: { surgery_type_id: number; surgery_subtype_id?: number | null; items: Array<{ label: string }> }) {
  return clinicRequest(session, "PUT", "checklist-template", body);
}

export async function ensureChecklist(session: Session, surgeryId: number) {
  return clinicRequest<ChecklistDto>(session, "POST", `surgeries/${surgeryId}/checklist/ensure`);
}

export async function toggleChecklistItem(session: Session, checklistId: number, itemId: number) {
  return clinicRequest<ChecklistDto>(session, "PATCH", `checklists/${checklistId}/items/${itemId}/toggle`);
}

export async function storeChecklistItem(session: Session, checklistId: number, label: string) {
  return clinicRequest<ChecklistDto>(session, "POST", `checklists/${checklistId}/items`, { label });
}

export async function updateChecklistItem(session: Session, checklistId: number, itemId: number, label: string) {
  return clinicRequest<ChecklistDto>(session, "PUT", `checklists/${checklistId}/items/${itemId}`, { label });
}

export async function destroyChecklistItem(session: Session, checklistId: number, itemId: number) {
  return clinicRequest<ChecklistDto>(session, "DELETE", `checklists/${checklistId}/items/${itemId}`);
}

export async function confirmChecklist(session: Session, checklistId: number) {
  return clinicRequest<{ message?: string; checklist?: ChecklistDto }>(session, "POST", `checklists/${checklistId}/confirm`);
}

export async function loadSystem(session: Session) {
  return clinicRequest<{
    backups?: Array<{ name: string; size?: number; at?: number }>;
    health?: { app?: boolean; db?: boolean; queue_pending?: number | null; queue_failed?: number | null };
    connection?: string;
    driver?: string;
    privacy?: { activity_log_days?: number; qr_cache_days?: number; retention_note?: string };
    is_admin?: boolean;
  }>(session, "GET", "system");
}

export async function runBackup(session: Session) {
  return clinicRequest<{ message?: string }>(session, "POST", "system/backup");
}

export async function restoreBackup(session: Session, file: string) {
  return clinicRequest<{ message?: string }>(session, "POST", "system/restore", { file, confirm: "RESTORE" });
}

export async function downloadBackup(session: Session, file: string) {
  return clinicDownload(session, `system/backup/${encodeURIComponent(file)}/download`, file);
}

export async function updatePrivacy(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "PUT", "system/privacy", body);
}

export async function loadSupport(session: Session) {
  return clinicRequest<{ telegram?: string; phone?: string; email?: string; sla_hours?: number; notes?: string }>(session, "GET", "support");
}

export async function loadHelp(session: Session) {
  return clinicRequest<{
    support?: { telegram?: string; phone?: string; email?: string; sla_hours?: number; notes?: string };
    retention_note?: string;
    sections?: Array<{ title: string; items: string[] }>;
  }>(session, "GET", "help");
}

export async function destroyExam(session: Session, patientId: number, visitId: number) {
  return clinicRequest(session, "DELETE", `patients/${patientId}/exams/${visitId}`);
}

export async function destroyNote(session: Session, patientId: number, noteId: number) {
  return clinicRequest(session, "DELETE", `patients/${patientId}/notes/${noteId}`);
}

export async function updateSupport(session: Session, body: Record<string, unknown>) {
  return clinicRequest(session, "PUT", "support", body);
}
