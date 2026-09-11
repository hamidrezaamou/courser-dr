package ir.mramo.archive.data

import com.google.gson.JsonElement
import com.google.gson.annotations.SerializedName

data class ApiOk(
    val ok: Boolean? = null,
    val message: String? = null,
)

data class LoginRequest(
    @SerializedName("national_code") val nationalCode: String,
    val password: String,
    @SerializedName("device_name") val deviceName: String = "android",
)

data class LoginResponse(
    val ok: Boolean? = null,
    val token: String? = null,
    val user: UserDto? = null,
    val clinic: ClinicDto? = null,
    val message: String? = null,
)

data class MeResponse(
    val ok: Boolean? = null,
    val user: UserDto? = null,
    val clinic: ClinicDto? = null,
)

data class UserDto(
    val id: Long = 0,
    val name: String? = null,
    @SerializedName("national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    val role: String? = null,
    @SerializedName("role_label") val roleLabel: String? = null,
    @SerializedName("photo_url") val photoUrl: String? = null,
    val initial: String? = null,
    @SerializedName("is_staff") val isStaff: Boolean = false,
    @SerializedName("can_manage_clinical") val canManageClinical: Boolean = false,
    @SerializedName("can_manage_appointments") val canManageAppointments: Boolean = false,
    @SerializedName("can_edit_patient") val canEditPatient: Boolean = false,
)

data class ClinicDto(
    val name: String? = null,
    @SerializedName("doctor_name") val doctorName: String? = null,
    val phone: String? = null,
    @SerializedName("logo_url") val logoUrl: String? = null,
    @SerializedName("clinic_label") val clinicLabel: String? = null,
)

data class StatDto(
    val key: String? = null,
    val label: String? = null,
    val value: Int = 0,
)

data class HomeResponse(
    val ok: Boolean? = null,
    val user: UserDto? = null,
    val clinic: ClinicDto? = null,
    @SerializedName("today_jalali") val todayJalali: String? = null,
    val stats: List<StatDto> = emptyList(),
    @SerializedName("today_visits") val todayVisits: List<BookingDto> = emptyList(),
    @SerializedName("today_surgeries") val todaySurgeries: List<BookingDto> = emptyList(),
    @SerializedName("upcoming_visits") val upcomingVisits: List<BookingDto> = emptyList(),
    @SerializedName("upcoming_surgeries") val upcomingSurgeries: List<BookingDto> = emptyList(),
    val patient: PatientDto? = null,
)

data class PatientDto(
    val id: Long = 0,
    val name: String? = null,
    @SerializedName("national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    @SerializedName("mobile_secondary") val mobileSecondary: String? = null,
    val age: String? = null,
    @SerializedName("photo_url") val photoUrl: String? = null,
    val initial: String? = null,
    @SerializedName("upcoming_visits_count") val upcomingVisitsCount: Int = 0,
    @SerializedName("upcoming_surgeries_count") val upcomingSurgeriesCount: Int = 0,
    @SerializedName("surgeries_count") val surgeriesCount: Int = 0,
)

data class PatientsResponse(
    val ok: Boolean? = null,
    val q: String? = null,
    val patients: List<PatientDto> = emptyList(),
    val meta: PageMeta? = null,
)

data class PageMeta(
    @SerializedName("current_page") val currentPage: Int = 1,
    @SerializedName("last_page") val lastPage: Int = 1,
    val total: Int = 0,
)

data class PatientDetailResponse(
    val ok: Boolean? = null,
    val patient: PatientDto? = null,
    val timeline: List<TimelineItemDto> = emptyList(),
)

data class TimelineItemDto(
    val id: String? = null,
    val type: String? = null,
    val payload: JsonElement? = null,
)

data class StatusActionDto(
    val id: String? = null,
    val label: String? = null,
    val hint: String? = null,
)

data class BookingDto(
    val id: Long = 0,
    val kind: String? = null,
    @SerializedName("patient_id") val patientId: Long? = null,
    @SerializedName("patient_name") val patientName: String? = null,
    @SerializedName("national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    val title: String? = null,
    val subtitle: String? = null,
    @SerializedName("hospital_name") val hospitalName: String? = null,
    @SerializedName("eye_side_label") val eyeSideLabel: String? = null,
    @SerializedName("scheduled_date_jalali") val scheduledDateJalali: String? = null,
    @SerializedName("scheduled_time_label") val scheduledTimeLabel: String? = null,
    val status: String? = null,
    @SerializedName("status_label") val statusLabel: String? = null,
    val notes: String? = null,
    val locked: Boolean = false,
    @SerializedName("is_emergency") val isEmergency: Boolean = false,
    val actions: List<StatusActionDto> = emptyList(),
)

data class BoardResponse(
    val ok: Boolean? = null,
    val kind: String? = null,
    val date: String? = null,
    val today: String? = null,
    val tomorrow: String? = null,
    val stats: BoardStatsDto? = null,
    val items: List<BookingDto> = emptyList(),
    val hospitals: List<HospitalDto> = emptyList(),
)

data class BoardStatsDto(
    val total: Int = 0,
    val confirmed: Int = 0,
    val scheduled: Int = 0,
    val waiting: Int = 0,
    val done: Int = 0,
    val cancelled: Int = 0,
)

data class HospitalDto(
    val id: Long = 0,
    val name: String? = null,
)

data class HospitalsResponse(
    val ok: Boolean? = null,
    val hospitals: List<HospitalDto> = emptyList(),
)

data class SlotDto(
    val value: String? = null,
    val label: String? = null,
    val booked: Boolean = false,
    val bookable: Boolean = false,
)

data class SlotsResponse(
    val slots: List<SlotDto> = emptyList(),
    val message: String? = null,
    @SerializedName("slot_mode") val slotMode: String? = null,
)

data class VisitCalendarResponse(
    val days: Map<String, DayCapacityDto> = emptyMap(),
    val message: String? = null,
)

data class DayCapacityDto(
    val total: Int = 0,
    val booked: Int = 0,
    val free: Int = 0,
    val status: String? = null,
)

data class SurgeryCatalogResponse(
    @SerializedName("hospital_id") val hospitalId: Long? = null,
    @SerializedName("hospital_name") val hospitalName: String? = null,
    val types: List<SurgeryTypeDto> = emptyList(),
    val days: Map<String, DayCapacityDto> = emptyMap(),
    val message: String? = null,
)

data class SurgeryTypeDto(
    val id: String? = null,
    val name: String? = null,
    @SerializedName("has_general") val hasGeneral: Boolean = false,
    val subtypes: List<SurgerySubtypeDto> = emptyList(),
)

data class SurgerySubtypeDto(
    val id: String? = null,
    val name: String? = null,
)

data class CreatePatientBody(
    val name: String,
    @SerializedName("national_code") val nationalCode: String,
    val mobile: String,
    val age: String? = null,
)

data class PatientMutationResponse(
    val ok: Boolean? = null,
    val message: String? = null,
    val patient: PatientDto? = null,
)

data class BookVisitBody(
    @SerializedName("patient_name") val patientName: String,
    @SerializedName("national_code") val nationalCode: String?,
    val mobile: String,
    val age: String? = null,
    @SerializedName("visit_type") val visitType: String? = "ویزیت",
    @SerializedName("scheduled_date") val scheduledDate: String,
    @SerializedName("scheduled_time") val scheduledTime: String,
    val reason: String? = null,
    val notes: String? = null,
)

data class BookSurgeryBody(
    @SerializedName("patient_name") val patientName: String,
    @SerializedName("national_code") val nationalCode: String?,
    val mobile: String,
    val age: String? = null,
    @SerializedName("hospital_id") val hospitalId: Long,
    @SerializedName("surgery_type") val surgeryType: String,
    @SerializedName("surgery_type_id") val surgeryTypeId: Long? = null,
    @SerializedName("surgery_subtype_id") val surgerySubtypeId: Long? = null,
    @SerializedName("eye_side") val eyeSide: String,
    @SerializedName("scheduled_date") val scheduledDate: String,
    @SerializedName("scheduled_time") val scheduledTime: String,
    @SerializedName("surgeon_name") val surgeonName: String? = null,
    val notes: String? = null,
    @SerializedName("is_emergency") val isEmergency: Boolean = false,
)

data class BookingMutationResponse(
    val ok: Boolean? = null,
    val message: String? = null,
    val appointment: BookingDto? = null,
    val surgery: BookingDto? = null,
)

data class StatusBody(val status: String)

data class ExamBody(
    val examination: String? = null,
    val history: String? = null,
    val diagnosis: String? = null,
    val treatment: String? = null,
    @SerializedName("next_instruction") val nextInstruction: String? = null,
    @SerializedName("eye_side") val eyeSide: String? = null,
    @SerializedName("va_right") val vaRight: String? = null,
    @SerializedName("va_left") val vaLeft: String? = null,
    @SerializedName("iop_right") val iopRight: String? = null,
    @SerializedName("iop_left") val iopLeft: String? = null,
)

data class NoteBody(val note: String)
