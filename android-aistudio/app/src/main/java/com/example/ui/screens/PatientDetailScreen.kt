package com.example.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.data.model.TimelineItem
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.screens.dialogs.BookSurgeryDialog
import com.example.ui.screens.dialogs.BookVisitDialog
import com.example.ui.screens.dialogs.ExamDialog
import com.example.ui.theme.*

@Composable
fun PatientDetailScreen(
    patientId: Long,
    viewModel: AppViewModel,
    onBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val detailState by viewModel.patientDetailUiState.collectAsState()
    val authState by viewModel.authUiState.collectAsState()

    var showBookVisitDialog by remember { mutableStateOf(false) }
    var showBookSurgeryDialog by remember { mutableStateOf(false) }
    var showExamDialog by remember { mutableStateOf(false) }

    LaunchedEffect(patientId) {
        viewModel.loadPatientDetail(patientId)
    }

    val patient = detailState.patient
    val canManageClinical = authState.user?.canManageClinical == true

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            ClinicTopBar(
                title = patient?.name ?: "پرونده بیمار",
                subtitle = "کد ملی: ${toPersianDigits(patient?.nationalCode ?: "—")}",
                onBack = onBack,
                onRefresh = { viewModel.loadPatientDetail(patientId) }
            )
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        if (detailState.isLoading && patient == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = BrandPrimary)
            }
        } else if (patient == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                PersianEmptyState(
                    message = detailState.errorMessage ?: "اطلاعات پرونده بیمار در دسترس نیست.",
                    icon = Icons.Default.FolderOff,
                    actionLabel = "تلاش مجدد",
                    onActionClick = { viewModel.loadPatientDetail(patientId) }
                )
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
                contentPadding = PaddingValues(vertical = 16.dp)
            ) {
                // Patient Header Info Card
                item {
                    ClinicCard(
                        backgroundColor = Color.White,
                        modifier = Modifier.fillMaxWidth().testTag("patient_info_card")
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(56.dp)
                                    .clip(CircleShape)
                                    .background(BrandSoft),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Person,
                                    contentDescription = null,
                                    tint = BrandDark,
                                    modifier = Modifier.size(32.dp)
                                )
                            }
                            Spacer(modifier = Modifier.width(14.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = patient.name ?: "—",
                                    fontSize = 18.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = BrandInk
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Row {
                                    Text(
                                        text = "کد ملی: ${toPersianDigits(patient.nationalCode ?: "—")}",
                                        fontSize = 13.sp,
                                        color = TextSecondary
                                    )
                                    if (patient.age != null && patient.age.toString().isNotBlank()) {
                                        Text(
                                            text = " • سن: ${toPersianDigits(patient.age.toString())} سال",
                                            fontSize = 13.sp,
                                            color = TextMuted
                                        )
                                    }
                                }
                                if (!patient.mobile.isNullOrBlank()) {
                                    Text(
                                        text = "تلفن همراه: ${toPersianDigits(patient.mobile)}",
                                        fontSize = 13.sp,
                                        color = TextSecondary
                                    )
                                }
                            }
                        }
                    }
                }

                // Action Buttons Bar
                item {
                    Text(
                        text = "عملیات پرونده:",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk,
                        modifier = Modifier.padding(horizontal = 4.dp)
                    )
                    Spacer(modifier = Modifier.height(8.dp))

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        // Dial Mobile Button
                        val mobile = patient.mobile
                        Button(
                            onClick = {
                                if (!mobile.isNullOrBlank()) {
                                    val dialIntent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$mobile"))
                                    context.startActivity(dialIntent)
                                }
                            },
                            enabled = !mobile.isNullOrBlank(),
                            colors = ButtonDefaults.buttonColors(containerColor = BrandDark),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.weight(1f).testTag("action_dial_button")
                        ) {
                            Icon(Icons.Default.Phone, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("تماس", fontSize = 12.sp, maxLines = 1)
                        }

                        // Book Visit Button
                        Button(
                            onClick = { showBookVisitDialog = true },
                            colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.weight(1f).testTag("action_book_visit_button")
                        ) {
                            Icon(Icons.Default.CalendarMonth, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("نوبت ویزیت", fontSize = 12.sp, maxLines = 1)
                        }

                        // Book Surgery Button
                        Button(
                            onClick = { showBookSurgeryDialog = true },
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF6C5CE7)),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.weight(1f).testTag("action_book_surgery_button")
                        ) {
                            Icon(Icons.Default.Healing, contentDescription = null, modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("نوبت جراحی", fontSize = 12.sp, maxLines = 1)
                        }
                    }

                    // Doctor / Admin Exam Button (only if can_manage_clinical)
                    if (canManageClinical) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(
                            onClick = { showExamDialog = true },
                            colors = ButtonDefaults.buttonColors(containerColor = BrandMint),
                            shape = RoundedCornerShape(12.dp),
                            modifier = Modifier.fillMaxWidth().testTag("action_exam_button")
                        ) {
                            Icon(Icons.Default.MedicalInformation, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("ثبت معاینه بالینی و دستور پزشک", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }

                // Timeline Header
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.History, contentDescription = null, tint = BrandPrimary, modifier = Modifier.size(20.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "خط زمانی پرونده و سوابق",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = BrandInk
                            )
                        }

                        Text(
                            text = "${toPersianDigits(detailState.timeline.size.toString())} رویداد",
                            fontSize = 12.sp,
                            color = TextMuted
                        )
                    }
                }

                // Timeline List
                if (detailState.timeline.isEmpty()) {
                    item {
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            PersianEmptyState(
                                message = "هنوز سابقه‌ای در خط زمانی این پرونده ثبت نشده است.",
                                icon = Icons.Default.Timeline
                            )
                        }
                    }
                } else {
                    items(detailState.timeline) { item ->
                        TimelineCard(item = item)
                    }
                }
            }
        }
    }

    if (showBookVisitDialog && patient != null) {
        BookVisitDialog(
            patient = patient,
            viewModel = viewModel,
            onDismiss = { showBookVisitDialog = false }
        )
    }

    if (showBookSurgeryDialog && patient != null) {
        BookSurgeryDialog(
            patient = patient,
            viewModel = viewModel,
            onDismiss = { showBookSurgeryDialog = false }
        )
    }

    if (showExamDialog && patient != null) {
        ExamDialog(
            patientId = patient.id ?: patientId,
            patientName = patient.name ?: "",
            viewModel = viewModel,
            onDismiss = { showExamDialog = false }
        )
    }
}

@Composable
private fun TimelineCard(item: TimelineItem) {
    val (typeTitle, icon, tintColor, bgColor) = when (item.type?.lowercase()) {
        "visit" -> Quad("ویزیت", Icons.Default.EventNote, BrandPrimary, BrandSoft)
        "appointment" -> Quad("نوبت", Icons.Default.AccessTime, Color(0xFFE76F51), Color(0xFFFDF3EE))
        "surgery" -> Quad("جراحی", Icons.Default.Healing, Color(0xFF6C5CE7), Color(0xFFF1EFFF))
        "note" -> Quad("یادداشت", Icons.Default.Note, Color(0xFF2F5F8C), Color(0xFFEDF5FC))
        "document" -> Quad("سند / فایل", Icons.Default.AttachFile, Color(0xFF455A64), Color(0xFFECEFF1))
        "prescription" -> Quad("نسخه / دارو", Icons.Default.Medication, BrandMint, Color(0xFFE8F7F3))
        else -> Quad(item.type ?: "رویداد", Icons.Default.Circle, BrandPrimary, BrandSoft)
    }

    ClinicCard(
        modifier = Modifier.fillMaxWidth().testTag("timeline_item_${item.id}")
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.Top
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(bgColor),
                contentAlignment = Alignment.Center
            ) {
                Icon(imageVector = icon, contentDescription = null, tint = tintColor, modifier = Modifier.size(22.dp))
            }

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = item.title ?: typeTitle,
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk
                    )

                    val dateStr = item.date ?: item.createdAt
                    if (!dateStr.isNullOrBlank()) {
                        Text(
                            text = toPersianDigits(dateStr),
                            fontSize = 11.sp,
                            color = TextMuted
                        )
                    }
                }

                if (!item.description.isNullOrBlank()) {
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = item.description,
                        fontSize = 13.sp,
                        color = TextSecondary,
                        lineHeight = 19.sp
                    )
                }

                // Render payload entries nicely if present
                val payload = item.payload
                val hiddenPayloadKeys = setOf(
                    "id", "kind", "source", "locked", "patient_id", "hospital_id",
                    "scheduled_date", "created_at", "has_voice", "has_drawing",
                    "voice_url", "drawing_url", "title", "subtitle", "summary",
                    "description", "patient_name", "national_code", "mobile", "age"
                )
                val visiblePayload = payload?.filter { (k, v) ->
                    k !in hiddenPayloadKeys && v.isNotBlank()
                }.orEmpty()
                if (visiblePayload.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(8.dp))
                    Surface(
                        modifier = Modifier.fillMaxWidth().clip(RoundedCornerShape(10.dp)),
                        color = MedicalBackground
                    ) {
                        Column(modifier = Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            visiblePayload.forEach { (k, v) ->
                                if (v.isNotBlank()) {
                                    val keyFa = when (k) {
                                        "status" -> "وضعیت"
                                        "status_label" -> "وضعیت"
                                        "hospital" -> "بیمارستان"
                                        "hospital_name" -> "بیمارستان"
                                        "doctor" -> "پزشک"
                                        "surgeon_name" -> "جراح"
                                        "creator_name" -> "ثبت‌کننده"
                                        "eye_side" -> "چشم"
                                        "eye_side_label" -> "چشم"
                                        "time" -> "ساعت"
                                        "scheduled_time" -> "ساعت"
                                        "scheduled_time_label" -> "ساعت"
                                        "scheduled_date_jalali" -> "تاریخ"
                                        "created_at_jalali" -> "تاریخ ثبت"
                                        "diagnosis" -> "تشخیص"
                                        "treatment" -> "درمان"
                                        "examination" -> "معاینه"
                                        "history" -> "شرح حال"
                                        "next_instruction" -> "دستور بعدی"
                                        "note" -> "یادداشت"
                                        "notes" -> "یادداشت"
                                        "va" -> "دید"
                                        "va_right" -> "دید راست"
                                        "va_left" -> "دید چپ"
                                        "iop" -> "فشار چشم"
                                        "iop_right" -> "فشار راست"
                                        "iop_left" -> "فشار چپ"
                                        "surgery_type" -> "نوع عمل"
                                        "visit_type" -> "نوع ویزیت"
                                        else -> k
                                    }
                                    Row {
                                        Text(
                                            text = "$keyFa: ",
                                            fontSize = 12.sp,
                                            fontWeight = FontWeight.SemiBold,
                                            color = BrandDark
                                        )
                                        Text(
                                            text = toPersianDigits(v.toString()),
                                            fontSize = 12.sp,
                                            color = TextSecondary
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

private data class Quad<A, B, C, D>(val first: A, val second: B, val third: C, val fourth: D)
