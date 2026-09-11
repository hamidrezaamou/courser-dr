package com.example.ui.screens.dialogs

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import com.example.data.model.CreateExamRequest
import com.example.data.model.Patient
import com.example.ui.AppViewModel
import com.example.ui.components.toPersianDigits
import com.example.ui.theme.*

@Composable
fun CreatePatientDialog(
    isCreating: Boolean,
    errorMessage: String?,
    onDismiss: () -> Unit,
    onSubmit: (name: String, nationalCode: String, mobile: String, age: String) -> Unit
) {
    var name by remember { mutableStateOf("") }
    var nationalCode by remember { mutableStateOf("") }
    var mobile by remember { mutableStateOf("") }
    var age by remember { mutableStateOf("") }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Card(
            modifier = Modifier
                .fillMaxWidth(0.92f)
                .widthIn(max = 480.dp)
                .clip(RoundedCornerShape(24.dp)),
            shape = RoundedCornerShape(24.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(
                modifier = Modifier
                    .padding(20.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = "ثبت بیمار جدید",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandDark
                    )
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "بستن", tint = TextMuted)
                    }
                }

                if (!errorMessage.isNullOrBlank()) {
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp)
                            .clip(RoundedCornerShape(10.dp)),
                        color = Color(0xFFFDECEE)
                    ) {
                        Text(
                            text = errorMessage,
                            color = StatusCancelled,
                            fontSize = 13.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("نام و نام خانوادگی بیمار *") },
                    singleLine = true,
                    shape = RoundedCornerShape(14.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("create_patient_name_input")
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = nationalCode,
                    onValueChange = { nationalCode = it },
                    label = { Text("کد ملی (۱۰ رقم) *") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    shape = RoundedCornerShape(14.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("create_patient_national_code_input")
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = mobile,
                    onValueChange = { mobile = it },
                    label = { Text("شماره همراه (مثال: ۰۹۱۲...)") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
                    shape = RoundedCornerShape(14.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("create_patient_mobile_input")
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = age,
                    onValueChange = { age = it },
                    label = { Text("سن بیمار") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    shape = RoundedCornerShape(14.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("create_patient_age_input")
                )

                Spacer(modifier = Modifier.height(20.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    OutlinedButton(
                        onClick = onDismiss,
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("انصراف")
                    }

                    Button(
                        onClick = { onSubmit(name, nationalCode, mobile, age) },
                        enabled = !isCreating && name.isNotBlank() && nationalCode.isNotBlank(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary),
                        modifier = Modifier
                            .weight(1f)
                            .testTag("submit_create_patient_button")
                    ) {
                        if (isCreating) {
                            CircularProgressIndicator(
                                color = Color.White,
                                modifier = Modifier.size(18.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Text("ثبت پرونده")
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun BookVisitDialog(
    patient: Patient,
    viewModel: AppViewModel,
    onDismiss: () -> Unit
) {
    val bookingState by viewModel.bookingState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.initVisitBooking()
    }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Card(
            modifier = Modifier
                .fillMaxWidth(0.94f)
                .widthIn(max = 500.dp)
                .clip(RoundedCornerShape(24.dp)),
            shape = RoundedCornerShape(24.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(
                modifier = Modifier
                    .padding(20.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Text(
                            text = "رزرو نوبت ویزیت",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandDark
                        )
                        Text(
                            text = "بیمار: ${patient.name ?: "—"}",
                            fontSize = 13.sp,
                            color = TextSecondary
                        )
                    }
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "بستن", tint = TextMuted)
                    }
                }

                if (!bookingState.errorMessage.isNullOrBlank()) {
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp)
                            .clip(RoundedCornerShape(10.dp)),
                        color = Color(0xFFFDECEE)
                    ) {
                        Text(
                            text = bookingState.errorMessage ?: "",
                            color = StatusCancelled,
                            fontSize = 13.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Date Selection Chips
                Text(
                    text = "انتخاب تاریخ ویزیت:",
                    fontSize = 14.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = BrandInk
                )
                Spacer(modifier = Modifier.height(8.dp))

                if (bookingState.isLoadingCalendar) {
                    Box(modifier = Modifier.fillMaxWidth().height(50.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = BrandPrimary, modifier = Modifier.size(24.dp))
                    }
                } else if (bookingState.calendarDays.isEmpty()) {
                    Text(
                        text = "تقویم نوبت‌دهی در دسترس نیست. تاریخ پیش‌فرض انتخاب می‌شود.",
                        fontSize = 12.sp,
                        color = TextMuted
                    )
                } else {
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        items(bookingState.calendarDays.keys.sorted()) { dayKey ->
                            val dayInfo = bookingState.calendarDays[dayKey]
                            val isSelected = bookingState.selectedDate == dayKey

                            Surface(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(12.dp))
                                    .clickable { viewModel.selectBookingDate(dayKey, "visit") }
                                    .border(
                                        width = if (isSelected) 2.dp else 1.dp,
                                        color = if (isSelected) BrandPrimary else SoftBorder,
                                        shape = RoundedCornerShape(12.dp)
                                    ),
                                color = if (isSelected) BrandSoft else Color.White
                            ) {
                                Column(
                                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally
                                ) {
                                    Text(
                                        text = toPersianDigits(dayKey),
                                        fontSize = 13.sp,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                        color = if (isSelected) BrandPrimary else BrandInk
                                    )
                                    if (dayInfo?.free != null) {
                                        Text(
                                            text = "${toPersianDigits(dayInfo.free.toString())} جای خالی",
                                            fontSize = 10.sp,
                                            color = BrandMint
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Time Slots Selection
                Text(
                    text = "انتخاب ساعت ویزیت:",
                    fontSize = 14.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = BrandInk
                )
                Spacer(modifier = Modifier.height(8.dp))

                if (bookingState.isLoadingSlots) {
                    Box(modifier = Modifier.fillMaxWidth().height(50.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = BrandPrimary, modifier = Modifier.size(24.dp))
                    }
                } else if (bookingState.slots.isEmpty()) {
                    Text(
                        text = "نوبت خالی برای تاریخ انتخاب شده یافت نشد.",
                        fontSize = 12.sp,
                        color = TextSecondary
                    )
                } else {
                    FlowRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        bookingState.slots.forEach { slot ->
                            val slotVal = slot.value ?: slot.label ?: ""
                            val isSelected = bookingState.selectedSlot == slotVal
                            val isBooked = slot.booked == true

                            FilterChip(
                                selected = isSelected,
                                onClick = {
                                    if (!isBooked) viewModel.selectSlot(slotVal)
                                },
                                enabled = !isBooked,
                                label = {
                                    Text(
                                        text = toPersianDigits(slot.label ?: slotVal),
                                        fontSize = 12.sp,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal
                                    )
                                },
                                shape = RoundedCornerShape(10.dp),
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = BrandPrimary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(24.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    OutlinedButton(
                        onClick = onDismiss,
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("انصراف")
                    }

                    Button(
                        onClick = {
                            viewModel.submitBookVisit(patient) {
                                onDismiss()
                            }
                        },
                        enabled = !bookingState.isSubmitting &&
                                !bookingState.selectedDate.isNullOrBlank() &&
                                !bookingState.selectedSlot.isNullOrBlank(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary),
                        modifier = Modifier
                            .weight(1f)
                            .testTag("submit_book_visit_button")
                    ) {
                        if (bookingState.isSubmitting) {
                            CircularProgressIndicator(
                                color = Color.White,
                                modifier = Modifier.size(18.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Text("تأیید نوبت")
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun BookSurgeryDialog(
    patient: Patient,
    viewModel: AppViewModel,
    onDismiss: () -> Unit
) {
    val bookingState by viewModel.bookingState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.initSurgeryBooking()
    }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Card(
            modifier = Modifier
                .fillMaxWidth(0.94f)
                .widthIn(max = 520.dp)
                .clip(RoundedCornerShape(24.dp)),
            shape = RoundedCornerShape(24.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(
                modifier = Modifier
                    .padding(20.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Text(
                            text = "رزرو وقت جراحی",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandDark
                        )
                        Text(
                            text = "بیمار: ${patient.name ?: "—"}",
                            fontSize = 13.sp,
                            color = TextSecondary
                        )
                    }
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "بستن", tint = TextMuted)
                    }
                }

                if (!bookingState.errorMessage.isNullOrBlank()) {
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp)
                            .clip(RoundedCornerShape(10.dp)),
                        color = Color(0xFFFDECEE)
                    ) {
                        Text(
                            text = bookingState.errorMessage ?: "",
                            color = StatusCancelled,
                            fontSize = 13.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // 1. Hospital Selection
                Text(text = "۱. بیمارستان محل جراحی:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                Spacer(modifier = Modifier.height(6.dp))
                LazyRow(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    items(bookingState.hospitals) { hosp ->
                        val isSelected = bookingState.selectedHospital?.id == hosp.id
                        FilterChip(
                            selected = isSelected,
                            onClick = { viewModel.selectHospital(hosp) },
                            label = { Text(hosp.name ?: "بیمارستان") },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = BrandPrimary,
                                selectedLabelColor = Color.White
                            )
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // 2. Surgery Type Selection
                if (bookingState.surgeryTypes.isNotEmpty()) {
                    Text(text = "۲. نوع جراحی:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                    Spacer(modifier = Modifier.height(6.dp))
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        items(bookingState.surgeryTypes) { sType ->
                            val isSelected = bookingState.selectedSurgeryType?.id == sType.id
                            FilterChip(
                                selected = isSelected,
                                onClick = { viewModel.selectSurgeryType(sType) },
                                label = { Text(sType.name ?: sType.title ?: "نوع جراحی") },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = BrandPrimary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                    }
                    Spacer(modifier = Modifier.height(14.dp))
                }

                if (bookingState.selectedSurgeryType != null &&
                    (bookingState.surgerySubtypes.isNotEmpty() || bookingState.selectedSurgeryType?.hasGeneral == true)
                ) {
                    Text(text = "۳. زیرگروه عمل:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                    Spacer(modifier = Modifier.height(6.dp))
                    FlowRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        if (bookingState.selectedSurgeryType?.hasGeneral == true) {
                            val isGeneral = bookingState.selectedSurgerySubtype == null
                            FilterChip(
                                selected = isGeneral,
                                onClick = { viewModel.selectSurgerySubtype(null) },
                                label = { Text("عمومی") },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = BrandPrimary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                        bookingState.surgerySubtypes.forEach { subtype ->
                            val isSelected = bookingState.selectedSurgerySubtype?.id == subtype.id
                            FilterChip(
                                selected = isSelected,
                                onClick = { viewModel.selectSurgerySubtype(subtype) },
                                label = { Text(subtype.name ?: subtype.title ?: "زیرگروه") },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = BrandPrimary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                    }
                    Spacer(modifier = Modifier.height(14.dp))
                }

                Text(text = "۴. روز عمل:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                Spacer(modifier = Modifier.height(6.dp))
                if (bookingState.isLoadingCalendar) {
                    Box(modifier = Modifier.fillMaxWidth().height(50.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = BrandPrimary, modifier = Modifier.size(24.dp))
                    }
                } else if (bookingState.calendarDays.isEmpty()) {
                    Text(
                        text = "برای این بیمارستان و نوع عمل، روز خالی در تقویم تایم‌ها ثبت نشده است.",
                        fontSize = 12.sp,
                        color = TextMuted
                    )
                } else {
                    LazyRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        items(bookingState.calendarDays.keys.sorted()) { dayKey ->
                            val dayInfo = bookingState.calendarDays[dayKey]
                            val isSelected = bookingState.selectedDate == dayKey
                            Surface(
                                modifier = Modifier
                                    .clip(RoundedCornerShape(12.dp))
                                    .clickable { viewModel.selectBookingDate(dayKey, "surgery") }
                                    .border(
                                        width = if (isSelected) 2.dp else 1.dp,
                                        color = if (isSelected) BrandPrimary else SoftBorder,
                                        shape = RoundedCornerShape(12.dp)
                                    ),
                                color = if (isSelected) BrandSoft else Color.White
                            ) {
                                Column(
                                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                                    horizontalAlignment = Alignment.CenterHorizontally
                                ) {
                                    Text(
                                        text = toPersianDigits(dayKey),
                                        fontSize = 13.sp,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                        color = if (isSelected) BrandPrimary else BrandInk
                                    )
                                    if (dayInfo?.free != null) {
                                        Text(
                                            text = "${toPersianDigits(dayInfo.free.toString())} جای خالی",
                                            fontSize = 10.sp,
                                            color = BrandMint
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                Text(text = "۵. چشم تحت جراحی:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                Spacer(modifier = Modifier.height(6.dp))
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    listOf("OD" to "چشم راست (OD)", "OS" to "چشم چپ (OS)", "OU" to "هر دو چشم (OU)").forEach { (code, title) ->
                        val isSelected = bookingState.eyeSide == code
                        FilterChip(
                            selected = isSelected,
                            onClick = { viewModel.setEyeSide(code) },
                            label = { Text(title) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = BrandMint,
                                selectedLabelColor = Color.White
                            )
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Time Slots
                Text(text = "۶. ساعت نوبت جراحی:", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandInk)
                Spacer(modifier = Modifier.height(6.dp))

                if (bookingState.isLoadingSlots) {
                    CircularProgressIndicator(color = BrandPrimary, modifier = Modifier.size(22.dp))
                } else if (bookingState.slots.isEmpty()) {
                    Text("در حال حاضر وقت خالی در دسترس نیست یا برای این تاریخ اعلام نشده است.", fontSize = 12.sp, color = TextMuted)
                } else {
                    FlowRow(
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        bookingState.slots.forEach { slot ->
                            val slotVal = slot.value ?: slot.label ?: ""
                            val isSelected = bookingState.selectedSlot == slotVal
                            val isBooked = slot.booked == true || slot.bookable == false
                            FilterChip(
                                selected = isSelected,
                                onClick = { if (!isBooked) viewModel.selectSlot(slotVal) },
                                enabled = !isBooked,
                                label = { Text(toPersianDigits(slot.label ?: slotVal)) },
                                colors = FilterChipDefaults.filterChipColors(
                                    selectedContainerColor = BrandPrimary,
                                    selectedLabelColor = Color.White
                                )
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(24.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    OutlinedButton(onClick = onDismiss, shape = RoundedCornerShape(12.dp), modifier = Modifier.weight(1f)) {
                        Text("انصراف")
                    }

                    Button(
                        onClick = {
                            viewModel.submitBookSurgery(patient) {
                                onDismiss()
                            }
                        },
                        enabled = !bookingState.isSubmitting &&
                                bookingState.selectedHospital != null &&
                                bookingState.selectedSurgeryType != null &&
                                !bookingState.selectedDate.isNullOrBlank() &&
                                !bookingState.selectedSlot.isNullOrBlank(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary),
                        modifier = Modifier.weight(1f).testTag("submit_book_surgery_button")
                    ) {
                        if (bookingState.isSubmitting) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                        } else {
                            Text("ثبت نوبت جراحی")
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ExamDialog(
    patientId: Long,
    patientName: String,
    viewModel: AppViewModel,
    onDismiss: () -> Unit
) {
    var examination by remember { mutableStateOf("") }
    var diagnosis by remember { mutableStateOf("") }
    var treatment by remember { mutableStateOf("") }
    var nextInstruction by remember { mutableStateOf("") }
    var eyeSide by remember { mutableStateOf("OD") }
    var vaRight by remember { mutableStateOf("") }
    var vaLeft by remember { mutableStateOf("") }
    var iopRight by remember { mutableStateOf("") }
    var iopLeft by remember { mutableStateOf("") }
    var isSubmitting by remember { mutableStateOf(false) }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Card(
            modifier = Modifier
                .fillMaxWidth(0.94f)
                .widthIn(max = 520.dp)
                .clip(RoundedCornerShape(24.dp)),
            shape = RoundedCornerShape(24.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White)
        ) {
            Column(
                modifier = Modifier
                    .padding(20.dp)
                    .verticalScroll(rememberScrollState())
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column {
                        Text(
                            text = "ثبت معاینه و دستور بالینی",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandDark
                        )
                        Text(
                            text = "بیمار: $patientName",
                            fontSize = 13.sp,
                            color = TextSecondary
                        )
                    }
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "بستن", tint = TextMuted)
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Eye side selector
                Text(text = "چشم مورد معاینه:", fontSize = 13.sp, color = BrandInk, fontWeight = FontWeight.Medium)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(vertical = 6.dp)) {
                    listOf("OD" to "راست (OD)", "OS" to "چپ (OS)", "OU" to "هر دو (OU)").forEach { (code, label) ->
                        FilterChip(
                            selected = eyeSide == code,
                            onClick = { eyeSide = code },
                            label = { Text(label) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = BrandMint,
                                selectedLabelColor = Color.White
                            )
                        )
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // VA Right and VA Left
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = vaRight,
                        onValueChange = { vaRight = it },
                        label = { Text("دید راست (VA OD)") },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp)
                    )
                    OutlinedTextField(
                        value = vaLeft,
                        onValueChange = { vaLeft = it },
                        label = { Text("دید چپ (VA OS)") },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp)
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                // IOP Right and IOP Left
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = iopRight,
                        onValueChange = { iopRight = it },
                        label = { Text("فشار راست (IOP OD)") },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp)
                    )
                    OutlinedTextField(
                        value = iopLeft,
                        onValueChange = { iopLeft = it },
                        label = { Text("فشار چپ (IOP OS)") },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp)
                    )
                }

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = examination,
                    onValueChange = { examination = it },
                    label = { Text("شرح معاینه بالینی (Examination)") },
                    minLines = 2,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = diagnosis,
                    onValueChange = { diagnosis = it },
                    label = { Text("تشخیص بالینی (Diagnosis)") },
                    minLines = 2,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = treatment,
                    onValueChange = { treatment = it },
                    label = { Text("طرح درمان و تجویز دارو (Treatment)") },
                    minLines = 2,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                Spacer(modifier = Modifier.height(10.dp))

                OutlinedTextField(
                    value = nextInstruction,
                    onValueChange = { nextInstruction = it },
                    label = { Text("دستورات بعدی / نوبت ویزیت بعدی") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                )

                Spacer(modifier = Modifier.height(20.dp))

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedButton(onClick = onDismiss, shape = RoundedCornerShape(12.dp), modifier = Modifier.weight(1f)) {
                        Text("انصراف")
                    }

                    Button(
                        onClick = {
                            isSubmitting = true
                            val req = CreateExamRequest(
                                examination = examination.takeIf { it.isNotBlank() },
                                diagnosis = diagnosis.takeIf { it.isNotBlank() },
                                treatment = treatment.takeIf { it.isNotBlank() },
                                nextInstruction = nextInstruction.takeIf { it.isNotBlank() },
                                eyeSide = eyeSide,
                                vaRight = vaRight.takeIf { it.isNotBlank() },
                                vaLeft = vaLeft.takeIf { it.isNotBlank() },
                                iopRight = iopRight.takeIf { it.isNotBlank() },
                                iopLeft = iopLeft.takeIf { it.isNotBlank() }
                            )
                            viewModel.submitExam(patientId, req) {
                                isSubmitting = false
                                onDismiss()
                            }
                        },
                        enabled = !isSubmitting && (examination.isNotBlank() || diagnosis.isNotBlank() || treatment.isNotBlank()),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = BrandMint),
                        modifier = Modifier.weight(1f).testTag("submit_exam_button")
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(18.dp))
                        } else {
                            Text("ثبت معاینه")
                        }
                    }
                }
            }
        }
    }
}
