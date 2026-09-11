package ir.mramo.archive.ui.patients

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.PersonAdd
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ir.mramo.archive.data.ApiException
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.PatientDto
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.AvatarBubble
import ir.mramo.archive.ui.components.EmptyState
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.MetaLine
import ir.mramo.archive.ui.theme.Brand
import ir.mramo.archive.ui.theme.BrandDark
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

@Composable
fun PatientsScreen(
    repo: ArchiveRepository,
    onOpen: (Long) -> Unit,
    onCreate: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var q by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var items by remember { mutableStateOf<List<PatientDto>>(emptyList()) }
    var debounce by remember { mutableStateOf<Job?>(null) }

    fun search(term: String) {
        debounce?.cancel()
        debounce = scope.launch {
            delay(280)
            loading = true
            error = null
            try {
                items = repo.patients(term).patients
            } catch (e: ApiException) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    LaunchedEffect(Unit) { search("") }

    Scaffold(
        floatingActionButton = {
            FloatingActionButton(onClick = onCreate, containerColor = Brand, contentColor = androidx.compose.ui.graphics.Color.White) {
                Icon(Icons.Rounded.PersonAdd, contentDescription = "ثبت بیمار")
            }
        },
    ) { padding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(16.dp),
        ) {
            Text("جستجوی بیمار", color = BrandDark, fontWeight = FontWeight.Bold, style = androidx.compose.material3.MaterialTheme.typography.headlineMedium)
            Spacer(Modifier.height(12.dp))
            ArchiveField(q, {
                q = it
                search(it)
            }, "نام، کد ملی یا موبایل")
            Spacer(Modifier.height(12.dp))
            ErrorBanner(error)
            when {
                loading -> LoadingBox()
                items.isEmpty() -> EmptyState("بیماری پیدا نشد")
                else -> LazyColumn(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    items(items, key = { it.id }) { patient ->
                        PatientRow(patient) { onOpen(patient.id) }
                    }
                }
            }
        }
    }
}

@Composable
private fun PatientRow(patient: PatientDto, onClick: () -> Unit) {
    ArchiveCard(Modifier.clickable(onClick = onClick)) {
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            AvatarBubble(patient.initial)
            Column(Modifier.weight(1f)) {
                Text(patient.name ?: "—", fontWeight = FontWeight.SemiBold, color = BrandDark)
                Spacer(Modifier.height(4.dp))
                MetaLine(listOf(patient.nationalCode, patient.mobile, patient.age?.let { "سن $it" }))
            }
        }
    }
}
