package com.example.data.model

import com.squareup.moshi.Json

data class LoginRequest(
    @Json(name = "national_code") val nationalCode: String,
    @Json(name = "password") val password: String,
    @Json(name = "device_name") val deviceName: String = "android"
)

data class LoginResponse(
    val ok: Boolean? = true,
    val token: String? = null,
    val user: User? = null,
    val clinic: Clinic? = null,
    val message: String? = null
)

data class User(
    val id: Long? = null,
    val name: String? = null,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    val role: String? = null,
    @Json(name = "role_label") val roleLabel: String? = null,
    @Json(name = "is_staff") val isStaff: Boolean = false,
    @Json(name = "can_manage_clinical") val canManageClinical: Boolean = false,
    @Json(name = "can_edit_patient") val canEditPatient: Boolean = false
)

data class Clinic(
    val id: Long? = null,
    val name: String? = null,
    @Json(name = "doctor_name") val doctorName: String? = null,
    val phone: String? = null,
    @Json(name = "clinic_label") val clinicLabel: String? = null
)

data class HomeResponse(
    val stats: List<StatItem>? = null,
    @Json(name = "today_jalali") val todayJalali: String? = null,
    @Json(name = "today_visits") val todayVisits: List<TodayVisit>? = null,
    @Json(name = "today_surgeries") val todaySurgeries: List<TodaySurgery>? = null,
    val clinic: Clinic? = null
)

data class StatItem(
    val key: String? = null,
    val label: String? = null,
    val value: Int? = null
)

data class TodayVisit(
    val id: Long? = null,
    @Json(name = "patient_id") val patientId: Long? = null,
    @Json(name = "patient_name") val patientName: String? = null,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    @Json(name = "scheduled_time") val scheduledTime: String? = null,
    @Json(name = "scheduled_time_label") val scheduledTimeLabel: String? = null,
    val status: String? = null,
    @Json(name = "status_label") val statusLabel: String? = null,
    @Json(name = "visit_type") val visitType: String? = null
)

data class TodaySurgery(
    val id: Long? = null,
    @Json(name = "patient_id") val patientId: Long? = null,
    @Json(name = "patient_name") val patientName: String? = null,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    @Json(name = "scheduled_time") val scheduledTime: String? = null,
    @Json(name = "scheduled_time_label") val scheduledTimeLabel: String? = null,
    val status: String? = null,
    @Json(name = "status_label") val statusLabel: String? = null,
    @Json(name = "surgery_type") val surgeryType: String? = null,
    @Json(name = "hospital_name") val hospitalName: String? = null,
    @Json(name = "eye_side") val eyeSide: String? = null
)

data class Patient(
    val id: Long? = null,
    val name: String? = null,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String? = null,
    val age: String? = null,
    val initial: String? = null
)

data class CreatePatientRequest(
    val name: String,
    @Json(name = "national_code") val nationalCode: String,
    val mobile: String,
    val age: String
)

data class PatientDetailResponse(
    val patient: Patient? = null,
    val timeline: List<TimelineItem>? = null
)

data class TimelineItem(
    val id: String? = null,
    val type: String? = null,
    val payload: Map<String, String>? = null,
    val date: String? = null,
    @Json(name = "created_at") val createdAt: String? = null,
    val title: String? = null,
    val description: String? = null
)

data class BoardResponse(
    val date: String? = null,
    val today: String? = null,
    val tomorrow: String? = null,
    val items: List<BoardItem>? = null
)

data class BoardItem(
    val id: Long? = null,
    @Json(name = "patient_id") val patientId: Long? = null,
    @Json(name = "patient_name") val patientName: String? = null,
    val title: String? = null,
    @Json(name = "scheduled_time_label") val scheduledTimeLabel: String? = null,
    @Json(name = "status_label") val statusLabel: String? = null,
    val status: String? = null,
    val locked: Boolean? = false,
    val actions: List<BoardAction>? = null,
    val kind: String? = null
)

data class BoardAction(
    val id: String? = null,
    val label: String? = null,
    val hint: String? = null
)

data class ChangeStatusRequest(
    val status: String
)

data class CalendarDayInfo(
    val total: Int? = null,
    val booked: Int? = null,
    val free: Int? = null,
    val status: String? = null
)

data class VisitCalendarResponse(
    val days: Map<String, CalendarDayInfo>? = null
)

data class Slot(
    val value: String? = null,
    val label: String? = null,
    val booked: Boolean? = null,
    val bookable: Boolean? = true
)

data class SlotsResponse(
    val slots: List<Slot>? = null
)

data class BookVisitRequest(
    @Json(name = "patient_name") val patientName: String,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String,
    val age: String? = null,
    @Json(name = "visit_type") val visitType: String = "ویزیت",
    @Json(name = "scheduled_date") val scheduledDate: String,
    @Json(name = "scheduled_time") val scheduledTime: String
)

data class Hospital(
    val id: Long? = null,
    val name: String? = null
)

data class SurgeryOption(
    val id: Long? = null,
    val name: String? = null,
    val title: String? = null,
    val label: String? = null,
    val hasGeneral: Boolean = true,
    val subtypes: List<SurgeryOption> = emptyList()
)

data class SurgeryCatalog(
    val types: List<SurgeryOption> = emptyList(),
    val days: Map<String, CalendarDayInfo> = emptyMap()
)

data class BookSurgeryRequest(
    @Json(name = "patient_name") val patientName: String,
    @Json(name = "national_code") val nationalCode: String? = null,
    val mobile: String,
    val age: String? = null,
    @Json(name = "hospital_id") val hospitalId: Long,
    @Json(name = "surgery_type") val surgeryType: String,
    @Json(name = "surgery_type_id") val surgeryTypeId: Long? = null,
    @Json(name = "surgery_subtype_id") val surgerySubtypeId: Long? = null,
    @Json(name = "eye_side") val eyeSide: String, // OD, OS, OU
    @Json(name = "scheduled_date") val scheduledDate: String,
    @Json(name = "scheduled_time") val scheduledTime: String
)

data class CreateExamRequest(
    val examination: String? = null,
    val diagnosis: String? = null,
    val treatment: String? = null,
    @Json(name = "next_instruction") val nextInstruction: String? = null,
    @Json(name = "eye_side") val eyeSide: String? = null,
    @Json(name = "va_right") val vaRight: String? = null,
    @Json(name = "va_left") val vaLeft: String? = null,
    @Json(name = "iop_right") val iopRight: String? = null,
    @Json(name = "iop_left") val iopLeft: String? = null
)

data class PatientProfileResponse(
    val patient: Patient? = null,
    val user: User? = null,
    val timeline: List<TimelineItem>? = null,
    val clinic: Clinic? = null,
    val visits: List<TodayVisit>? = null,
    val surgeries: List<TodaySurgery>? = null
)

data class SimpleApiResponse(
    val ok: Boolean? = null,
    val message: String? = null
)
