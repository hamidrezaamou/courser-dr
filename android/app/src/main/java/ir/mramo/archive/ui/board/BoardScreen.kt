package ir.mramo.archive.ui.board

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import ir.mramo.archive.data.BoardResponse
import ir.mramo.archive.data.BookingDto
import ir.mramo.archive.data.StatusActionDto
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.EmptyState
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.LoadingBox
import ir.mramo.archive.ui.components.MetaLine
import ir.mramo.archive.ui.components.StatTile
import ir.mramo.archive.ui.components.StatusChip
import ir.mramo.archive.ui.home.statusColor
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.Muted
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BoardScreen(repo: ArchiveRepository, onOpenPatient: (Long) -> Unit) {
    val scope = rememberCoroutineScope()
    var kind by remember { mutableStateOf("surgery") }
    var date by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(true) }
    var refreshing by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var data by remember { mutableStateOf<BoardResponse?>(null) }
    var pending by remember { mutableStateOf<Pair<BookingDto, StatusActionDto>?>(null) }

    fun load(fromRefresh: Boolean = false) {
        scope.launch {
            if (fromRefresh) refreshing = true else loading = true
            error = null
            try {
                data = repo.board(kind, date)
                if (date == null) date = data?.date
            } catch (e: ApiException) {
                error = e.message
            } finally {
                loading = false
                refreshing = false
            }
        }
    }

    LaunchedEffect(kind, date) { load() }

    PullToRefreshBox(isRefreshing = refreshing, onRefresh = { load(true) }, modifier = Modifier.fillMaxSize()) {
        Column(Modifier.fillMaxSize().padding(16.dp)) {
            Text("بُرد نوبت‌ها", color = BrandDark, fontWeight = FontWeight.Bold, style = androidx.compose.material3.MaterialTheme.typography.headlineMedium)
            Spacer(Modifier.height(4.dp))
            Text(data?.date ?: "", color = Muted)
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(selected = kind == "visit", onClick = { kind = "visit" }, label = { Text("ویزیت") })
                FilterChip(selected = kind == "surgery", onClick = { kind = "surgery" }, label = { Text("عمل") })
                data?.today?.let { today ->
                    FilterChip(selected = date == today, onClick = { date = today }, label = { Text("امروز") })
                }
                data?.tomorrow?.let { tom ->
                    FilterChip(selected = date == tom, onClick = { date = tom }, label = { Text("فردا") })
                }
            }
            Spacer(Modifier.height(12.dp))
            ErrorBanner(error)
            val stats = data?.stats
            if (stats != null) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    StatTile("کل", stats.total.toString(), Modifier.weight(1f))
                    StatTile("تأیید", stats.confirmed.toString(), Modifier.weight(1f))
                    StatTile("صف", stats.waiting.toString(), Modifier.weight(1f))
                }
                Spacer(Modifier.height(12.dp))
            }
            when {
                loading && data == null -> LoadingBox()
                data?.items.isNullOrEmpty() -> EmptyState("نوبتی در این روز نیست")
                else -> LazyColumn(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    items(data?.items.orEmpty(), key = { "${it.kind}-${it.id}" }) { item ->
                        BoardCard(item, onOpenPatient) { action -> pending = item to action }
                    }
                }
            }
        }
    }

    val dialog = pending
    if (dialog != null) {
        AlertDialog(
            onDismissRequest = { pending = null },
            title = { Text(dialog.second.label ?: "تغییر وضعیت") },
            text = { Text(dialog.second.hint ?: "") },
            confirmButton = {
                TextButton(onClick = {
                    val (item, action) = dialog
                    pending = null
                    scope.launch {
                        try {
                            repo.updateStatus(item.kind ?: "visit", item.id, action.id ?: return@launch)
                            load(true)
                        } catch (e: ApiException) {
                            error = e.message
                        }
                    }
                }) { Text("تأیید") }
            },
            dismissButton = { TextButton(onClick = { pending = null }) { Text("انصراف") } },
        )
    }
}

@Composable
private fun BoardCard(item: BookingDto, onOpenPatient: (Long) -> Unit, onAction: (StatusActionDto) -> Unit) {
    ArchiveCard {
        Row(horizontalArrangement = Arrangement.SpaceBetween) {
            Column(Modifier.weight(1f)) {
                Text(item.patientName ?: "—", fontWeight = FontWeight.SemiBold, color = BrandDark)
                Spacer(Modifier.height(4.dp))
                MetaLine(listOf(item.scheduledTimeLabel, item.title, item.hospitalName, item.eyeSideLabel, item.mobile))
            }
            StatusChip(item.statusLabel ?: "", statusColor(item.status))
        }
        if (item.actions.isNotEmpty() && !item.locked) {
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                item.actions.take(3).forEach { action ->
                    FilterChip(selected = false, onClick = { onAction(action) }, label = { Text(action.label ?: "") })
                }
            }
        }
        if (item.patientId != null) {
            Spacer(Modifier.height(6.dp))
            TextButtonLike(onClick = { onOpenPatient(item.patientId) })
        }
    }
}

@Composable
private fun TextButtonLike(onClick: () -> Unit) {
    TextButton(onClick = onClick) { Text("باز کردن پرونده") }
}
