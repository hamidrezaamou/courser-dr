package ir.mramo.archive.ui.patients

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.rounded.ArrowBack
import androidx.compose.material.icons.rounded.Call
import androidx.compose.material.icons.rounded.Event
import androidx.compose.material.icons.rounded.LocalHospital
import androidx.compose.material.icons.rounded.NoteAdd
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilledTonalButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.Gson
import ir.mramo.archive.data.ApiException
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.BookingDto
import ir.mramo.archive.data.PatientDetailResponse
import ir.mramo.archive.data.UserDto
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.AvatarBubble
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.MetaLine
import ir.mramo.archive.ui.components.SectionTitle
import ir.mramo.archive.ui.components.StatusChip
import ir.mramo.archive.ui.home.statusColor
import ir.mramo.archive.ui.theme.Brand
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.Muted
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PatientDetailScreen(
    repo: ArchiveRepository,
    patientId: Long,
    user: UserDto?,
    onBack: () -> Unit,
    onBookVisit: (Long) -> Unit,
    onBookSurgery: (Long) -> Unit,
    onExam: (Long) -> Unit,
) {
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    val gson = remember { Gson() }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var data by remember { mutableStateOf<PatientDetailResponse?>(null) }

    LaunchedEffect(patientId) {
        loading = true
        error = null
        try {
            data = repo.patient(patientId)
        } catch (e: ApiException) {
            error = e.message
        } finally {
            loading = false
        }
    }

    val patient = data?.patient
    Scaffold(
        containerColor = Surface,
        topBar = {
            TopAppBar(
                title = { Text(patient?.name ?: "پرونده") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Rounded.ArrowBack, null) }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Surface),
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
            ArchiveCard {
                Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    AvatarBubble(patient?.initial)
                    Column {
                        Text(patient?.name ?: "—", fontWeight = FontWeight.Bold, color = BrandDark, style = MaterialTheme.typography.titleLarge)
                        Spacer(Modifier.height(4.dp))
                        MetaLine(listOf(patient?.nationalCode, patient?.mobile, patient?.age?.let { "سن $it" }))
                    }
                }
            }
            Spacer(Modifier.height(12.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                if (!patient?.mobile.isNullOrBlank()) {
                    FilledTonalButton(onClick = {
                        context.startActivity(Intent(Intent.ACTION_DIAL, Uri.parse("tel:${patient?.mobile}")))
                    }, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Rounded.Call, null)
                        Text(" تماس")
                    }
                }
                FilledTonalButton(onClick = { onBookVisit(patientId) }, modifier = Modifier.weight(1f)) {
                    Icon(Icons.Rounded.Event, null)
                    Text(" ویزیت")
                }
            }
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                FilledTonalButton(onClick = { onBookSurgery(patientId) }, modifier = Modifier.weight(1f)) {
                    Icon(Icons.Rounded.LocalHospital, null)
                    Text(" عمل")
                }
                if (user?.canManageClinical == true) {
                    FilledTonalButton(onClick = { onExam(patientId) }, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Rounded.NoteAdd, null)
                        Text(" معاینه")
                    }
                }
            }
            Spacer(Modifier.height(18.dp))
            SectionTitle("تایم‌لاین پرونده")
            Spacer(Modifier.height(8.dp))
            data?.timeline.orEmpty().forEach { item ->
                val payload = item.payload
                ArchiveCard {
                    when (item.type) {
                        "appointment", "surgery" -> {
                            val booking = runCatching { gson.fromJson(payload, BookingDto::class.java) }.getOrNull()
                            Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                                Column(Modifier.weight(1f)) {
                                    Text(booking?.title ?: item.type.orEmpty(), fontWeight = FontWeight.SemiBold, color = BrandDark)
                                    Spacer(Modifier.height(4.dp))
                                    MetaLine(listOf(booking?.scheduledDateJalali, booking?.scheduledTimeLabel, booking?.hospitalName ?: booking?.eyeSideLabel))
                                }
                                StatusChip(booking?.statusLabel ?: "", statusColor(booking?.status))
                            }
                        }
                        "visit" -> {
                            Text("معاینه", fontWeight = FontWeight.SemiBold, color = Brand)
                            val exam = payload?.asJsonObject
                            val text = listOf("examination", "diagnosis", "summary")
                                .mapNotNull { key -> exam?.get(key)?.takeIf { it.isJsonPrimitive }?.asString }
                                .firstOrNull()
                            if (!text.isNullOrBlank()) {
                                Spacer(Modifier.height(6.dp))
                                Text(text, color = Muted)
                            }
                            val whenText = exam?.get("created_at_jalali")?.asString
                            if (!whenText.isNullOrBlank()) {
                                Spacer(Modifier.height(4.dp))
                                Text(whenText, color = Muted, style = MaterialTheme.typography.bodyMedium)
                            }
                        }
                        "note" -> {
                            Text("یادداشت داخلی", fontWeight = FontWeight.SemiBold, color = BrandDark)
                            Spacer(Modifier.height(6.dp))
                            Text(payload?.asJsonObject?.get("note")?.asString ?: "", color = Muted)
                        }
                        "document" -> {
                            Text("مدارک / تصویر", fontWeight = FontWeight.SemiBold, color = BrandDark)
                            val desc = payload?.asJsonObject?.get("description")?.asString
                            if (!desc.isNullOrBlank()) Text(desc, color = Muted)
                        }
                        "prescription" -> Text(payload?.asJsonObject?.get("summary")?.asString ?: "نسخه", fontWeight = FontWeight.SemiBold)
                        else -> Text(item.type ?: "رویداد")
                    }
                }
                Spacer(Modifier.height(8.dp))
            }
        }
    }
}
