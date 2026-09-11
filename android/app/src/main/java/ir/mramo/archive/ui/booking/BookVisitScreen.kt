package ir.mramo.archive.ui.booking

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.rounded.ArrowBack
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import ir.mramo.archive.data.ApiException
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.BookVisitBody
import ir.mramo.archive.data.PatientDto
import ir.mramo.archive.data.SlotDto
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.components.SectionTitle
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun BookVisitScreen(
    repo: ArchiveRepository,
    patientId: Long,
    onBack: () -> Unit,
    onDone: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var patient by remember { mutableStateOf<PatientDto?>(null) }
    var days by remember { mutableStateOf<List<String>>(emptyList()) }
    var date by remember { mutableStateOf<String?>(null) }
    var slots by remember { mutableStateOf<List<SlotDto>>(emptyList()) }
    var slot by remember { mutableStateOf<String?>(null) }
    var visitType by remember { mutableStateOf("ویزیت") }
    var loading by remember { mutableStateOf(true) }
    var saving by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(patientId) {
        loading = true
        try {
            patient = repo.patient(patientId).patient
            val calendar = repo.visitCalendar()
            days = calendar.days.filter { it.value.status != "full" && it.value.free > 0 }.keys.sorted()
            date = days.firstOrNull()
        } catch (e: ApiException) {
            error = e.message
        } finally {
            loading = false
        }
    }

    LaunchedEffect(date) {
        val selected = date ?: return@LaunchedEffect
        try {
            slots = repo.slots(selected, "visit").slots
            slot = slots.firstOrNull { it.bookable }?.value
        } catch (e: ApiException) {
            error = e.message
        }
    }

    Scaffold(
        containerColor = Surface,
        topBar = {
            TopAppBar(
                title = { Text("ثبت ویزیت") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Rounded.ArrowBack, null) } },
            )
        },
    ) { padding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
        ) {
            ErrorBanner(error)
            if (loading) {
                LoadingBox()
                return@Column
            }
            Text(patient?.name ?: "", style = androidx.compose.material3.MaterialTheme.typography.titleLarge)
            Spacer(Modifier.height(12.dp))
            ArchiveField(visitType, { visitType = it }, "نوع ویزیت")
            Spacer(Modifier.height(14.dp))
            SectionTitle("روز نوبت")
            Spacer(Modifier.height(8.dp))
            if (days.isEmpty()) {
                Text("هیچ روز ویزیت بازی در تایم‌ها نیست.")
            } else {
                FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    days.take(24).forEach { day ->
                        FilterChip(selected = date == day, onClick = { date = day }, label = { Text(day) })
                    }
                }
            }
            Spacer(Modifier.height(14.dp))
            SectionTitle("ساعت / نوبت")
            Spacer(Modifier.height(8.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                slots.filter { it.bookable }.forEach { item ->
                    FilterChip(
                        selected = slot == item.value,
                        onClick = { slot = item.value },
                        label = { Text(item.label ?: item.value ?: "") },
                    )
                }
            }
            Spacer(Modifier.height(22.dp))
            PrimaryButton("ثبت نوبت", saving, enabled = date != null && slot != null && patient != null) {
                val p = patient ?: return@PrimaryButton
                val d = date ?: return@PrimaryButton
                val s = slot ?: return@PrimaryButton
                saving = true
                error = null
                scope.launch {
                    try {
                        repo.storeVisit(
                            patientId,
                            BookVisitBody(
                                patientName = p.name.orEmpty(),
                                nationalCode = p.nationalCode,
                                mobile = p.mobile.orEmpty(),
                                age = p.age,
                                visitType = visitType,
                                scheduledDate = d,
                                scheduledTime = s,
                            ),
                        )
                        onDone()
                    } catch (e: ApiException) {
                        error = e.message
                    } finally {
                        saving = false
                    }
                }
            }
        }
    }
}
