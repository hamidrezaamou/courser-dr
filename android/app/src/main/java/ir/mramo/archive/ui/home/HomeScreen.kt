package ir.mramo.archive.ui.home

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
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
import ir.mramo.archive.data.BookingDto
import ir.mramo.archive.data.HomeResponse
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.EmptyState
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.MetaLine
import ir.mramo.archive.ui.components.SectionTitle
import ir.mramo.archive.ui.components.StatTile
import ir.mramo.archive.ui.components.StatusChip
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.Muted
import ir.mramo.archive.ui.theme.Success
import ir.mramo.archive.ui.theme.Waiting
import ir.mramo.archive.ui.theme.Warm
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    repo: ArchiveRepository,
    onOpenPatient: (Long) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var loading by remember { mutableStateOf(true) }
    var refreshing by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var data by remember { mutableStateOf<HomeResponse?>(null) }

    fun load(fromRefresh: Boolean = false) {
        scope.launch {
            if (fromRefresh) refreshing = true else loading = true
            error = null
            try {
                data = repo.home()
            } catch (e: ApiException) {
                error = e.message
            } finally {
                loading = false
                refreshing = false
            }
        }
    }

    LaunchedEffect(Unit) { load() }

    PullToRefreshBox(isRefreshing = refreshing, onRefresh = { load(true) }, modifier = Modifier.fillMaxSize()) {
        Column(
            Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
        ) {
            val clinic = data?.clinic
            val user = data?.user
            Text(clinic?.name ?: "آرشیو بیمار", color = BrandDark, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(4.dp))
            Text(
                listOfNotNull(user?.name, user?.roleLabel, data?.todayJalali).joinToString("  ·  "),
                color = Muted,
            )
            Spacer(Modifier.height(16.dp))
            ErrorBanner(error)
            when {
                loading && data == null -> LoadingBox()
                else -> {
                    val stats = data?.stats.orEmpty().take(4)
                    stats.chunked(2).forEach { row ->
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.padding(bottom = 10.dp)) {
                            row.forEach { stat ->
                                StatTile(stat.label ?: "", stat.value.toString(), modifier = Modifier.weight(1f))
                            }
                            if (row.size == 1) Spacer(Modifier.weight(1f))
                        }
                    }
                    Spacer(Modifier.height(8.dp))
                    BookingBlock("ویزیت‌های امروز", data?.todayVisits.orEmpty().ifEmpty { data?.upcomingVisits.orEmpty() }, onOpenPatient)
                    Spacer(Modifier.height(14.dp))
                    BookingBlock("عمل‌های امروز", data?.todaySurgeries.orEmpty().ifEmpty { data?.upcomingSurgeries.orEmpty() }, onOpenPatient)
                }
            }
        }
    }
}

@Composable
private fun BookingBlock(title: String, items: List<BookingDto>, onOpenPatient: (Long) -> Unit) {
    SectionTitle(title)
    Spacer(Modifier.height(8.dp))
    if (items.isEmpty()) {
        EmptyState("موردی برای امروز نیست")
        return
    }
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        items.forEach { item -> BookingMini(item) { item.patientId?.let(onOpenPatient) } }
    }
}

@Composable
fun BookingMini(item: BookingDto, onClick: () -> Unit) {
    ArchiveCard(Modifier.clickable(onClick = onClick)) {
        Row(horizontalArrangement = Arrangement.SpaceBetween) {
            Column(Modifier.weight(1f)) {
                Text(item.patientName ?: "—", fontWeight = FontWeight.SemiBold, color = BrandDark)
                Spacer(Modifier.height(4.dp))
                MetaLine(listOf(item.title, item.scheduledTimeLabel, item.hospitalName ?: item.eyeSideLabel))
            }
            StatusChip(item.statusLabel ?: item.status.orEmpty(), statusColor(item.status))
        }
    }
}

fun statusColor(status: String?) = when (status) {
    "confirmed" -> Success
    "done" -> BrandDark
    "cancelled", "no_show" -> Warm
    "waiting", "ready", "in_consult" -> Waiting
    else -> Muted
}
