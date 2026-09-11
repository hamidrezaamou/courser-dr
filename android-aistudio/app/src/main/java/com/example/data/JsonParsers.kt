package com.example.data

import com.example.data.model.*
import org.json.JSONArray
import org.json.JSONObject

internal fun JSONObject.str(key: String): String? =
    if (isNull(key)) null else optString(key).takeIf { it.isNotBlank() && it != "null" }

internal fun JSONObject.longOrNull(key: String): Long? {
    if (isNull(key) || !has(key)) return null
    return when (val raw = opt(key)) {
        is Number -> raw.toLong()
        is String -> raw.toLongOrNull()
        else -> null
    }
}

internal fun JSONObject.intOrNull(key: String): Int? {
    if (isNull(key) || !has(key)) return null
    return when (val raw = opt(key)) {
        is Number -> raw.toInt()
        is String -> raw.toIntOrNull()
        else -> null
    }
}

internal fun JSONObject.bool(key: String): Boolean = optBoolean(key, false)

internal fun JSONArray?.objects(): List<JSONObject> {
    if (this == null) return emptyList()
    return (0 until length()).mapNotNull { i -> optJSONObject(i) }
}

fun parseHome(json: String): HomeResponse {
    val root = JSONObject(json)
    val stats = root.optJSONArray("stats").objects().map { o ->
        StatItem(
            key = o.str("key"),
            label = o.str("label"),
            value = o.intOrNull("value") ?: o.str("value")?.toIntOrNull(),
        )
    }
    return HomeResponse(
        stats = stats,
        todayJalali = root.str("today_jalali"),
        todayVisits = root.optJSONArray("today_visits").objects().map { parseBookingVisit(it) },
        todaySurgeries = root.optJSONArray("today_surgeries").objects().map { parseBookingSurgery(it) },
        clinic = root.optJSONObject("clinic")?.let { parseClinic(it) },
    )
}

fun parseClinic(o: JSONObject) = Clinic(
    name = o.str("name"),
    doctorName = o.str("doctor_name"),
    phone = o.str("phone"),
    clinicLabel = o.str("clinic_label"),
)

fun parseUser(o: JSONObject) = User(
    id = o.longOrNull("id"),
    name = o.str("name"),
    nationalCode = o.str("national_code"),
    mobile = o.str("mobile"),
    role = o.str("role"),
    roleLabel = o.str("role_label"),
    isStaff = o.bool("is_staff"),
    canManageClinical = o.bool("can_manage_clinical"),
    canEditPatient = o.bool("can_edit_patient"),
)

fun parseLogin(json: String): LoginResponse {
    val root = JSONObject(json)
    return LoginResponse(
        ok = root.optBoolean("ok", true),
        token = root.str("token"),
        user = root.optJSONObject("user")?.let { parseUser(it) },
        clinic = root.optJSONObject("clinic")?.let { parseClinic(it) },
        message = root.str("message"),
    )
}

fun parseMe(json: String): Pair<User?, Clinic?> {
    val root = JSONObject(json)
    val user = root.optJSONObject("user")?.let { parseUser(it) }
        ?: runCatching { parseUser(root) }.getOrNull()
    val clinic = root.optJSONObject("clinic")?.let { parseClinic(it) }
    return user to clinic
}

fun parsePatient(o: JSONObject) = Patient(
    id = o.longOrNull("id"),
    name = o.str("name"),
    nationalCode = o.str("national_code"),
    mobile = o.str("mobile"),
    age = o.str("age") ?: o.intOrNull("age")?.toString(),
    initial = o.str("initial"),
)

fun parsePatients(json: String): List<Patient> {
    val root = JSONObject(json)
    val arr = when {
        root.has("patients") -> root.optJSONArray("patients")
        root.has("data") -> root.optJSONArray("data")
        else -> null
    }
    return arr.objects().map { parsePatient(it) }
}

fun parsePatientDetail(json: String): PatientDetailResponse {
    val root = JSONObject(json)
    val patientObj = root.optJSONObject("patient") ?: root
    return PatientDetailResponse(
        patient = parsePatient(patientObj),
        timeline = root.optJSONArray("timeline").objects().map { parseTimeline(it) },
    )
}

fun parseTimeline(o: JSONObject): TimelineItem {
    val payloadObj = o.optJSONObject("payload")
    val payload = flattenPayload(payloadObj)
    val type = o.str("type")
    val title = payload["title"]
        ?: payload["status_label"]
        ?: when (type) {
            "visit" -> payload["examination"]?.take(40) ?: "معاینه"
            "appointment" -> "نوبت ویزیت"
            "surgery" -> payload["title"] ?: "نوبت عمل"
            "note" -> "یادداشت داخلی"
            "document" -> "مدارک"
            "prescription" -> "نسخه دارو"
            else -> type
        }
    val date = payload["scheduled_date_jalali"]
        ?: payload["created_at_jalali"]
        ?: o.str("date")
    val description = payload["subtitle"]
        ?: payload["hospital_name"]
        ?: payload["note"]
        ?: payload["examination"]
        ?: payload["diagnosis"]
        ?: payload["summary"]
        ?: payload["description"]
    return TimelineItem(
        id = o.str("id") ?: o.longOrNull("id")?.toString(),
        type = type,
        payload = payload,
        date = date,
        title = title,
        description = description,
    )
}

private fun flattenPayload(obj: JSONObject?): Map<String, String> {
    if (obj == null) return emptyMap()
    val out = mutableMapOf<String, String>()
    val keys = obj.keys()
    while (keys.hasNext()) {
        val key = keys.next()
        if (obj.isNull(key)) continue
        val value = obj.opt(key) ?: continue
        when (value) {
            is JSONObject, is JSONArray -> Unit
            else -> {
                val text = value.toString()
                if (text.isNotBlank() && text != "null") out[key] = text
            }
        }
    }
    return out
}

fun parseBoard(json: String): BoardResponse {
    val root = JSONObject(json)
    return BoardResponse(
        date = root.str("date"),
        today = root.str("today"),
        tomorrow = root.str("tomorrow"),
        items = root.optJSONArray("items").objects().map { parseBoardItem(it) },
    )
}

fun parseBoardItem(o: JSONObject) = BoardItem(
    id = o.longOrNull("id"),
    patientId = o.longOrNull("patient_id"),
    patientName = o.str("patient_name"),
    title = o.str("title"),
    scheduledTimeLabel = o.str("scheduled_time_label") ?: o.str("scheduled_time"),
    statusLabel = o.str("status_label"),
    status = o.str("status"),
    locked = o.bool("locked"),
    kind = o.str("kind"),
    actions = o.optJSONArray("actions").objects().map {
        BoardAction(id = it.str("id"), label = it.str("label"), hint = it.str("hint"))
    },
)

fun parseBookingVisit(o: JSONObject) = TodayVisit(
    id = o.longOrNull("id"),
    patientId = o.longOrNull("patient_id"),
    patientName = o.str("patient_name"),
    nationalCode = o.str("national_code"),
    mobile = o.str("mobile"),
    scheduledTime = o.str("scheduled_time"),
    scheduledTimeLabel = o.str("scheduled_time_label"),
    status = o.str("status"),
    statusLabel = o.str("status_label"),
    visitType = o.str("title") ?: o.str("visit_type"),
)

fun parseBookingSurgery(o: JSONObject) = TodaySurgery(
    id = o.longOrNull("id"),
    patientId = o.longOrNull("patient_id"),
    patientName = o.str("patient_name"),
    nationalCode = o.str("national_code"),
    mobile = o.str("mobile"),
    scheduledTime = o.str("scheduled_time"),
    scheduledTimeLabel = o.str("scheduled_time_label"),
    status = o.str("status"),
    statusLabel = o.str("status_label"),
    surgeryType = o.str("title") ?: o.str("surgery_type"),
    hospitalName = o.str("hospital_name") ?: o.str("subtitle"),
    eyeSide = o.str("eye_side"),
)

fun parseHospitals(json: String): List<Hospital> {
    val root = JSONObject(json)
    val arr = root.optJSONArray("hospitals") ?: root.optJSONArray("data")
    return arr.objects().map {
        Hospital(id = it.longOrNull("id"), name = it.str("name"))
    }
}

fun parseSurgeryCatalog(json: String): Pair<List<SurgeryOption>, Map<String, CalendarDayInfo>> {
    val root = JSONObject(json)
    val types = root.optJSONArray("types").objects().map { t ->
        val subtypes = t.optJSONArray("subtypes").objects().map { s ->
            SurgeryOption(
                id = s.longOrNull("id") ?: s.str("id")?.toLongOrNull(),
                name = s.str("name"),
            )
        }
        SurgeryOption(
            id = t.longOrNull("id") ?: t.str("id")?.toLongOrNull(),
            name = t.str("name") ?: t.str("title"),
            hasGeneral = t.optBoolean("has_general", true),
            subtypes = subtypes,
        )
    }
    val daysObj = root.optJSONObject("days")
    val days = mutableMapOf<String, CalendarDayInfo>()
    if (daysObj != null) {
        val keys = daysObj.keys()
        while (keys.hasNext()) {
            val key = keys.next()
            val d = daysObj.optJSONObject(key) ?: continue
            days[key] = CalendarDayInfo(
                total = d.intOrNull("total"),
                booked = d.intOrNull("booked"),
                free = d.intOrNull("free"),
                status = d.str("status"),
            )
        }
    }
    return types to days
}

fun parseVisitCalendar(json: String): VisitCalendarResponse {
    val root = JSONObject(json)
    val daysObj = root.optJSONObject("days") ?: root
    val days = mutableMapOf<String, CalendarDayInfo>()
    val keys = daysObj.keys()
    while (keys.hasNext()) {
        val key = keys.next()
        val d = daysObj.optJSONObject(key) ?: continue
        days[key] = CalendarDayInfo(
            total = d.intOrNull("total"),
            booked = d.intOrNull("booked"),
            free = d.intOrNull("free"),
            status = d.str("status"),
        )
    }
    return VisitCalendarResponse(days = days)
}

fun parseSlots(json: String): List<Slot> {
    val root = JSONObject(json)
    val arr = root.optJSONArray("slots")
    if (arr != null && arr.length() > 0) {
        return arr.objects().map {
            Slot(
                value = it.str("value"),
                label = it.str("label"),
                booked = it.optBoolean("booked", false),
                bookable = it.optBoolean("bookable", true),
            )
        }
    }
    val available = root.optJSONArray("available_times") ?: JSONArray()
    return (0 until available.length()).mapNotNull { i ->
        val value = available.optString(i).takeIf { it.isNotBlank() } ?: return@mapNotNull null
        Slot(value = value, label = value, booked = false, bookable = true)
    }
}
