export type UserDto = {
  id: number;
  name?: string | null;
  national_code?: string | null;
  mobile?: string | null;
  role?: string | null;
  role_label?: string | null;
  photo_url?: string | null;
  initial?: string | null;
  is_staff?: boolean;
  can_manage_clinical?: boolean;
  can_manage_appointments?: boolean;
  can_edit_patient?: boolean;
  can_view_reports?: boolean;
  can_manage_settings?: boolean;
  can_access_clinic_settings?: boolean;
  can_access_modules?: boolean;
};

export type ClinicDto = {
  name?: string | null;
  doctor_name?: string | null;
  phone?: string | null;
  logo_url?: string | null;
  clinic_label?: string | null;
};

export type StatDto = {
  key?: string | null;
  label?: string | null;
  value?: number;
};

export type StatusActionDto = {
  id?: string | null;
  label?: string | null;
  hint?: string | null;
};

export type BookingDto = {
  id: number;
  kind?: string | null;
  patient_id?: number | null;
  patient_name?: string | null;
  national_code?: string | null;
  mobile?: string | null;
  mobile_secondary?: string | null;
  title?: string | null;
  subtitle?: string | null;
  hospital_name?: string | null;
  hospital_id?: number | null;
  surgery_type_id?: number | null;
  surgery_subtype_id?: number | null;
  surgery_subtype_name?: string | null;
  eye_side?: string | null;
  eye_side_label?: string | null;
  surgeon_name?: string | null;
  visit_type?: string | null;
  reason?: string | null;
  age?: string | null;
  scheduled_date?: string | null;
  scheduled_date_jalali?: string | null;
  weekday?: string | null;
  scheduled_time?: string | null;
  scheduled_time_label?: string | null;
  status?: string | null;
  status_label?: string | null;
  notes?: string | null;
  sms_body?: string | null;
  locked?: boolean;
  is_emergency?: boolean;
  is_exception?: boolean;
  note_count?: number;
  actions?: StatusActionDto[];
};

export type HomeResponse = {
  ok?: boolean;
  user?: UserDto;
  clinic?: ClinicDto;
  today_jalali?: string | null;
  stats?: StatDto[];
  today_visits?: BookingDto[];
  today_surgeries?: BookingDto[];
  upcoming_visits?: BookingDto[];
  upcoming_surgeries?: BookingDto[];
  patient?: PatientDto | null;
};

export type PatientDto = {
  id: number;
  name?: string | null;
  national_code?: string | null;
  mobile?: string | null;
  mobile_secondary?: string | null;
  age?: string | null;
  photo_url?: string | null;
  initial?: string | null;
  upcoming_visits_count?: number;
  upcoming_surgeries_count?: number;
  surgeries_count?: number;
};

export type PageMeta = {
  current_page?: number;
  last_page?: number;
  total?: number;
};

export type PatientsResponse = {
  ok?: boolean;
  q?: string | null;
  patients?: PatientDto[];
  stats?: { total?: number; new_today?: number; upcoming_surgery?: number };
  meta?: PageMeta;
};

export type TimelineItemDto = {
  id?: string | null;
  type?: string | null;
  payload?: Record<string, unknown> | null;
};

export type PatientDetailResponse = {
  ok?: boolean;
  patient?: PatientDto | null;
  timeline?: TimelineItemDto[];
};

export type BoardStatsDto = {
  total?: number;
  confirmed?: number;
  scheduled?: number;
  waiting?: number;
  done?: number;
  cancelled?: number;
  no_show?: number;
  pending_approval?: number;
};

export type ReminderItemDto = {
  patient_id?: number | null;
  name?: string | null;
  mobile?: string | null;
  national_code?: string | null;
  mobile_secondary?: string | null;
  time?: string | null;
  kind?: string | null;
  label?: string | null;
  booking?: BookingDto;
};

export type BoardResponse = {
  ok?: boolean;
  kind?: string | null;
  date?: string | null;
  today?: string | null;
  tomorrow?: string | null;
  hospitals?: HospitalDto[];
  stats?: BoardStatsDto | null;
  items?: BookingDto[];
  reminder_items?: ReminderItemDto[];
  reminders_enabled?: boolean;
  board_reminders_enabled?: boolean;
  sms_enabled?: boolean;
  sms_driver?: string | null;
  sms_live?: boolean;
  approval_enabled?: boolean;
  global_pending_approval?: number;
};

export type LoginResponse = {
  ok?: boolean;
  token?: string | null;
  user?: UserDto | null;
  clinic?: ClinicDto | null;
  message?: string | null;
};

export type Session = {
  token: string;
  site: string;
  user: UserDto;
  clinic?: ClinicDto | null;
};

export type BookingPrefill = {
  patient_id?: number | null;
  name?: string | null;
  mobile?: string | null;
  national_code?: string | null;
  date?: string | null;
  notes?: string | null;
  waiting_id?: number | null;
};

export type Route =
  | { name: "dashboard" }
  | { name: "patient"; id: number }
  | { name: "edit-patient"; id: number }
  | { name: "create-patient" }
  | { name: "board" }
  | { name: "floor" }
  | { name: "reports" }
  | { name: "followups" }
  | { name: "settings"; section?: "times" | "hospitals" | "types" | "drugs" | "programs" | "follow-ups" | "contacts" | "patients" | "checklist" }
  | { name: "modules"; module?: "hub" | "waiting" | "accounting" | "approval" | "billing" | "consent" | "portal" | "quality" | "eye_chart" | "rx_print" }
  | { name: "admin"; section?: "overview" | "users" | "features" | "quick-links" | "communications" | "brand" | "audit" | "his" | "system" | "support" }
  | { name: "prints"; surgeryId?: number }
  | { name: "messages" }
  | { name: "ready-answers" }
  | { name: "help" }
  | { name: "account" }
  | { name: "book-visit"; patientId?: number; bookingId?: number }
  | { name: "book-surgery"; patientId?: number; bookingId?: number; prefill?: BookingPrefill };

export type PanelFlags = {
  clinic_floor?: boolean;
  followups?: boolean;
  modules?: boolean;
  reports_export?: boolean;
  ready_answers?: boolean;
  surgery_checklist?: boolean;
};

export type PanelModule = {
  key: string;
  label: string;
  hint?: string;
};

export type QuickLink = {
  title: string;
  url: string;
};

export type PanelBootstrap = {
  ok?: boolean;
  flags?: PanelFlags;
  modules?: PanelModule[];
  quick_links?: QuickLink[];
};

export type ReportsResponse = {
  ok?: boolean;
  kind?: string;
  from?: string;
  to?: string;
  today?: string;
  yesterday?: string;
  tomorrow?: string;
  emergency?: boolean;
  hospitals?: HospitalDto[];
  surgery_types?: Array<{ id: number; name?: string; subtypes?: Array<{ id: number; name?: string }> }>;
  status_labels?: Record<string, string>;
  summary?: { visit?: number; surgery?: number };
  can_export?: boolean;
  rows?: BookingDto[];
};

export type FloorResponse = {
  ok?: boolean;
  date?: string;
  today?: string;
  kind?: string;
  upcoming?: BookingDto[];
  waiting?: BookingDto[];
  ready?: BookingDto[];
  in_consult?: BookingDto[];
};

export type FollowUpItem = {
  id: number;
  title?: string | null;
  status?: string | null;
  display_status?: string | null;
  status_label?: string | null;
  source?: string | null;
  source_label?: string | null;
  kind?: string | null;
  kind_label?: string | null;
  method?: string | null;
  method_label?: string | null;
  outcome?: string | null;
  outcome_label?: string | null;
  outcome_notes?: string | null;
  description?: string | null;
  due_jalali?: string | null;
  patient_id?: number | null;
  patient_name?: string | null;
  patient_mobile?: string | null;
  hospital_name?: string | null;
  surgery_type_name?: string | null;
  surgery_subtype_name?: string | null;
  assignee_name?: string | null;
  parent_id?: number | null;
  open?: boolean;
};

export type FollowupOption = { slug: string; label: string };

export type FollowupsResponse = {
  ok?: boolean;
  available?: boolean;
  bucket?: string;
  counts?: Record<string, number>;
  items?: FollowUpItem[];
  kinds?: FollowupOption[];
  methods?: FollowupOption[];
  outcomes?: FollowupOption[];
  statuses?: FollowupOption[];
  hospitals?: Array<{ id: number; name?: string }>;
  surgery_types?: Array<{ id: number; name?: string }>;
  staff?: Array<{ id: number; name?: string }>;
};

export type DayCapacityDto = {
  total?: number;
  booked?: number;
  free?: number;
  status?: string | null;
};

export type VisitCalendarResponse = {
  days?: Record<string, DayCapacityDto>;
  message?: string | null;
};

export type SlotBookingDto = {
  id?: number;
  patient_name?: string | null;
  mobile?: string | null;
  national_code?: string | null;
  surgery_type?: string | null;
  subtype_name?: string | null;
  eye_side?: string | null;
  surgeon_name?: string | null;
  status?: string | null;
  status_label?: string | null;
  is_exception?: boolean;
  is_emergency?: boolean;
};

export type SlotDto = {
  value?: string | null;
  label?: string | null;
  booked?: boolean;
  bookable?: boolean;
  removed?: boolean;
  exception?: boolean;
  booking?: SlotBookingDto | null;
};

export type SlotsResponse = {
  slots?: SlotDto[];
  message?: string | null;
  slot_mode?: string | null;
};

export type HospitalDto = {
  id: number;
  name?: string | null;
  address?: string | null;
  phone?: string | null;
};

export type SurgerySubtypeDto = {
  id?: string | null;
  name?: string | null;
};

export type SurgeryTypeDto = {
  id?: string | null;
  name?: string | null;
  has_general?: boolean;
  subtypes?: SurgerySubtypeDto[];
};

export type SurgeryCatalogResponse = {
  hospital_id?: number | null;
  hospital_name?: string | null;
  types?: SurgeryTypeDto[];
  days?: Record<string, DayCapacityDto>;
  message?: string | null;
};

export type BookVisitBody = {
  patient_name: string;
  national_code?: string | null;
  no_national_code?: boolean;
  mobile: string;
  mobile_secondary?: string | null;
  age?: string | null;
  visit_type?: string | null;
  reason?: string | null;
  notes?: string | null;
  scheduled_date: string;
  scheduled_time: string;
};

export type BookSurgeryBody = {
  patient_name: string;
  national_code?: string | null;
  no_national_code?: boolean;
  mobile: string;
  age?: string | null;
  hospital_id: number;
  surgery_type: string;
  surgery_type_id?: number | null;
  surgery_subtype_id?: number | null;
  eye_side: string;
  scheduled_date: string;
  scheduled_time: string;
  hospital_name?: string | null;
  surgeon_name?: string | null;
  notes?: string | null;
  is_emergency?: boolean;
  is_exception?: boolean;
  mobile_secondary?: string | null;
};

export const STATUS_LABELS: Record<string, string> = {
  scheduled: "ثبت‌شده",
  confirmed: "تأییدشده",
  waiting: "حضور / در صف",
  ready: "ارجاع به پزشک",
  in_consult: "نزد پزشک",
  cancelled: "لغوشده",
  done: "انجام‌شده",
  no_show: "عدم حضور",
  pending_approval: "در انتظار تأیید",
};
