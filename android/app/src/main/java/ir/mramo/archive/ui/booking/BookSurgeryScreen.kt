package ir.mramo.archive.ui.booking

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
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
import ir.mramo.archive.data.BookSurgeryBody
import ir.mramo.archive.data.HospitalDto
import ir.mramo.archive.data.PatientDto
import ir.mramo.archive.data.SlotDto
import ir.mramo.archive.data.SurgeryTypeDto
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.components.SectionTitle
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun BookSurgeryScreen(
    repo: ArchiveRepository,
    patientId: Long,
    onBack: () -> Unit,
    onDone: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var patient by remember { mutableStateOf<PatientDto?>(null) }
    var hospitals by remember { mutableStateOf<List<HospitalDto>>(emptyList()) }
    var hospitalId by remember { mutableStateOf<Long?>(null) }
    var types by remember { mutableStateOf<List<SurgeryTypeDto>>(emptyList()) }
    var type by remember { mutableStateOf<SurgeryTypeDto?>(null) }
    var subtypeId by remember { mutableStateOf<Long?>(null) }
    var days by remember { mutableStateOf<List<String>>(emptyList()) }
    var date by remember { mutableStateOf<String?>(null) }
    var slots by remember { mutableStateOf<List<SlotDto>>(emptyList()) }
    var slot by remember { mutableStateOf<String?>(null) }
    var eye by remember { mutableStateOf("OD") }
    var loading by remember { mutableStateOf(true) }
    var saving by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(patientId) {
        loading = true
        try {
            patient = repo.patient(patientId).patient
            hospitals = repo.hospitals().hospitals
            hospitalId = hospitals.firstOrNull()?.id
        } catch (e: ApiException) {
            error = e.message
        } finally {
            loading = false
        }
    }

    LaunchedEffect(hospitalId) {
        val hid = hospitalId ?: return@LaunchedEffect
        try {
            types = repo.surgeryCatalog(hid).types
            type = types.firstOrNull()
            subtypeId = null
        } catch (e: ApiException) {
            error = e.message
        }
    }

    LaunchedEffect(hospitalId, type?.id, subtypeId) {
        val hid = hospitalId ?: return@LaunchedEffect
        val tid = type?.id?.toLongOrNull() ?: return@LaunchedEffect
        try {
            val cal = repo.surgeryCalendar(hid, tid, subtypeId)
            days = cal.days.filter { it.value.status != "full" }.keys.sorted()
            date = days.firstOrNull()
        } catch (e: ApiException) {
            error = e.message
        }
    }

    LaunchedEffect(date, hospitalId, type?.id, subtypeId) {
        val d = date ?: return@LaunchedEffect
        val hid = hospitalId ?: return@LaunchedEffect
        val tid = type?.id?.toLongOrNull() ?: return@LaunchedEffect
        try {
            slots = repo.slots(d, "surgery", hid, tid, subtypeId).slots
            slot = slots.firstOrNull { it.bookable }?.value
        } catch (e: ApiException) {
            error = e.message
        }
    }

    Scaffold(
        containerColor = Surface,
        topBar = {
            TopAppBar(
                title = { Text("ثبت عمل") },
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
            Spacer(Modifier.height(14.dp))
            SectionTitle("بیمارستان")
            Spacer(Modifier.height(8.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                hospitals.forEach { h ->
                    FilterChip(selected = hospitalId == h.id, onClick = { hospitalId = h.id }, label = { Text(h.name ?: "") })
                }
            }
            Spacer(Modifier.height(14.dp))
            SectionTitle("نوع عمل")
            Spacer(Modifier.height(8.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                types.forEach { t ->
                    FilterChip(selected = type?.id == t.id, onClick = {
                        type = t
                        subtypeId = null
                    }, label = { Text(t.name ?: "") })
                }
            }
            val subtypes = type?.subtypes.orEmpty()
            if (subtypes.isNotEmpty()) {
                Spacer(Modifier.height(14.dp))
                SectionTitle("زیرگروه")
                Spacer(Modifier.height(8.dp))
                FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(selected = subtypeId == null, onClick = { subtypeId = null }, label = { Text("عمومی") })
                    subtypes.forEach { s ->
                        val id = s.id?.toLongOrNull()
                        FilterChip(selected = subtypeId == id, onClick = { subtypeId = id }, label = { Text(s.name ?: "") })
                    }
                }
            }
            Spacer(Modifier.height(14.dp))
            SectionTitle("چشم")
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                listOf("OD" to "راست", "OS" to "چپ", "OU" to "هر دو").forEach { (v, l) ->
                    FilterChip(selected = eye == v, onClick = { eye = v }, label = { Text(l) })
                }
            }
            Spacer(Modifier.height(14.dp))
            SectionTitle("روز عمل")
            Spacer(Modifier.height(8.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                days.take(20).forEach { day ->
                    FilterChip(selected = date == day, onClick = { date = day }, label = { Text(day) })
                }
            }
            Spacer(Modifier.height(14.dp))
            SectionTitle("نوبت")
            Spacer(Modifier.height(8.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                slots.filter { it.bookable }.forEach { item ->
                    FilterChip(selected = slot == item.value, onClick = { slot = item.value }, label = { Text(item.label ?: item.value ?: "") })
                }
            }
            Spacer(Modifier.height(22.dp))
            PrimaryButton("ثبت نوبت عمل", saving, enabled = patient != null && hospitalId != null && type != null && date != null && slot != null) {
                val p = patient ?: return@PrimaryButton
                saving = true
                error = null
                scope.launch {
                    try {
                        repo.storeSurgery(
                            patientId,
                            BookSurgeryBody(
                                patientName = p.name.orEmpty(),
                                nationalCode = p.nationalCode,
                                mobile = p.mobile.orEmpty(),
                                age = p.age,
                                hospitalId = hospitalId ?: return@launch,
                                surgeryType = type?.name.orEmpty(),
                                surgeryTypeId = type?.id?.toLongOrNull(),
                                surgerySubtypeId = subtypeId,
                                eyeSide = eye,
                                scheduledDate = date ?: return@launch,
                                scheduledTime = slot ?: return@launch,
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
