package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
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
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.data.model.BoardAction
import com.example.data.model.BoardItem
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.theme.*

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun BoardScreen(
    viewModel: AppViewModel,
    onNavigateToPatient: (Long) -> Unit,
    modifier: Modifier = Modifier
) {
    val boardState by viewModel.boardUiState.collectAsState()
    val boardData = boardState.boardData

    // State for action confirmation dialog
    var pendingActionItem by remember { mutableStateOf<BoardItem?>(null) }
    var pendingAction by remember { mutableStateOf<BoardAction?>(null) }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            ClinicTopBar(
                title = "بُرد درمانگاه",
                subtitle = "مدیریت وضعیت نوبت‌های روزانه",
                onRefresh = { viewModel.loadBoard() }
            )
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // Kind Selector Tabs: ویزیت | جراحی
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                shape = RoundedCornerShape(16.dp),
                color = BrandSoft
            ) {
                Row(modifier = Modifier.padding(4.dp)) {
                    val isVisit = boardState.kind == "visit"
                    Surface(
                        modifier = Modifier
                            .weight(1f)
                            .clip(RoundedCornerShape(12.dp))
                            .clickable { viewModel.setBoardKind("visit") }
                            .testTag("board_tab_visit"),
                        color = if (isVisit) BrandPrimary else Color.Transparent
                    ) {
                        Row(
                            modifier = Modifier.padding(vertical = 10.dp),
                            horizontalArrangement = Arrangement.Center,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                imageVector = Icons.Default.EventNote,
                                contentDescription = null,
                                tint = if (isVisit) Color.White else BrandDark,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "ویزیت‌ها",
                                fontSize = 14.sp,
                                fontWeight = if (isVisit) FontWeight.Bold else FontWeight.Medium,
                                color = if (isVisit) Color.White else BrandDark
                            )
                        }
                    }

                    val isSurgery = boardState.kind == "surgery"
                    Surface(
                        modifier = Modifier
                            .weight(1f)
                            .clip(RoundedCornerShape(12.dp))
                            .clickable { viewModel.setBoardKind("surgery") }
                            .testTag("board_tab_surgery"),
                        color = if (isSurgery) BrandPrimary else Color.Transparent
                    ) {
                        Row(
                            modifier = Modifier.padding(vertical = 10.dp),
                            horizontalArrangement = Arrangement.Center,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                imageVector = Icons.Default.Healing,
                                contentDescription = null,
                                tint = if (isSurgery) Color.White else BrandDark,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "جراحی‌ها",
                                fontSize = 14.sp,
                                fontWeight = if (isSurgery) FontWeight.Bold else FontWeight.Medium,
                                color = if (isSurgery) Color.White else BrandDark
                            )
                        }
                    }
                }
            }

            // Date Filter Chips (Today, Tomorrow, or specific Jalali date)
            val todayDate = boardData?.today
            val tomorrowDate = boardData?.tomorrow
            val currentDate = boardState.date ?: boardData?.date ?: todayDate

            LazyRow(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                item {
                    FilterChip(
                        selected = boardState.date == null || boardState.date == todayDate,
                        onClick = { viewModel.setBoardDate(todayDate) },
                        label = {
                            Text(
                                text = if (!todayDate.isNullOrBlank()) "امروز (${toPersianDigits(todayDate)})" else "امروز"
                            )
                        },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = BrandDark,
                            selectedLabelColor = Color.White
                        )
                    )
                }

                if (!tomorrowDate.isNullOrBlank()) {
                    item {
                        FilterChip(
                            selected = boardState.date == tomorrowDate,
                            onClick = { viewModel.setBoardDate(tomorrowDate) },
                            label = { Text("فردا (${toPersianDigits(tomorrowDate)})") },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = BrandDark,
                                selectedLabelColor = Color.White
                            )
                        )
                    }
                }

                if (!currentDate.isNullOrBlank() && currentDate != todayDate && currentDate != tomorrowDate) {
                    item {
                        FilterChip(
                            selected = true,
                            onClick = { },
                            label = { Text(toPersianDigits(currentDate)) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = BrandDark,
                                selectedLabelColor = Color.White
                            )
                        )
                    }
                }
            }

            // Board Content
            if (boardState.isLoading && boardData == null) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .weight(1f),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = BrandPrimary)
                }
            } else {
                val items = boardData?.items ?: emptyList()
                if (items.isEmpty()) {
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .weight(1f),
                        contentAlignment = Alignment.Center
                    ) {
                        PersianEmptyState(
                            message = "موردی در بُرد برای تاریخ و بخش انتخاب‌شده وجود ندارد.",
                            icon = Icons.Default.FactCheck
                        )
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .weight(1f)
                            .padding(horizontal = 16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp),
                        contentPadding = PaddingValues(vertical = 12.dp)
                    ) {
                        items(items) { item ->
                            BoardItemCard(
                                item = item,
                                onOpenPatient = { item.patientId?.let { onNavigateToPatient(it) } },
                                onActionClick = { action ->
                                    pendingActionItem = item
                                    pendingAction = action
                                }
                            )
                        }
                    }
                }
            }
        }
    }

    // Confirmation Dialog using action.hint
    val itemToUpdate = pendingActionItem
    val actionToPerform = pendingAction
    if (itemToUpdate != null && actionToPerform != null) {
        val hintText = actionToPerform.hint?.ifBlank { null }
            ?: "آیا از تغییر وضعیت نوبت بیمار «${itemToUpdate.patientName ?: ""}» به «${actionToPerform.label ?: actionToPerform.id ?: ""}» اطمینان دارید؟"

        PersianConfirmDialog(
            title = "تأیید تغییر وضعیت",
            message = hintText,
            confirmLabel = actionToPerform.label ?: "تأیید",
            dismissLabel = "انصراف",
            onConfirm = {
                viewModel.changeAppointmentStatus(
                    itemId = itemToUpdate.id ?: 0,
                    status = actionToPerform.id ?: "confirmed",
                    kind = itemToUpdate.kind ?: boardState.kind
                )
                pendingActionItem = null
                pendingAction = null
            },
            onDismiss = {
                pendingActionItem = null
                pendingAction = null
            }
        )
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun BoardItemCard(
    item: BoardItem,
    onOpenPatient: () -> Unit,
    onActionClick: (BoardAction) -> Unit
) {
    ClinicCard(
        modifier = Modifier
            .fillMaxWidth()
            .testTag("board_item_${item.id}")
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.weight(1f)
            ) {
                InitialsAvatar(
                    name = item.patientName,
                    size = 40.dp
                )

                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = item.patientName ?: "بیمار بدون نام",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandInk
                        )
                        if (item.locked == true) {
                            Spacer(modifier = Modifier.width(6.dp))
                            Icon(
                                imageVector = Icons.Default.Lock,
                                contentDescription = "قفل شده",
                                tint = StatusWaiting,
                                modifier = Modifier.size(15.dp)
                            )
                        }
                    }

                    if (!item.title.isNullOrBlank()) {
                        Spacer(modifier = Modifier.height(2.dp))
                        Text(
                            text = item.title,
                            fontSize = 12.sp,
                            color = BrandInk.copy(alpha = 0.6f)
                        )
                    }

                    Spacer(modifier = Modifier.height(4.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Schedule,
                            contentDescription = null,
                            tint = BrandInk.copy(alpha = 0.5f),
                            modifier = Modifier.size(13.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = toPersianDigits(item.scheduledTimeLabel ?: "--:--"),
                            fontSize = 11.sp,
                            color = BrandDark,
                            fontWeight = FontWeight.Medium
                        )

                        if (item.patientId != null) {
                            Spacer(modifier = Modifier.width(10.dp))
                            Text(
                                text = "مشاهده پرونده",
                                fontSize = 11.sp,
                                color = BrandPrimary,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier
                                    .clickable(onClick = onOpenPatient)
                                    .testTag("board_open_patient_${item.id}")
                            )
                        }
                    }
                }
            }

            StatusBadge(
                status = item.status,
                label = item.statusLabel
            )
        }

        // Action Buttons from API
        val actions = item.actions
        if (!actions.isNullOrEmpty()) {
            Spacer(modifier = Modifier.height(12.dp))
            HorizontalDivider(color = SoftBorder, thickness = 0.8.dp)
            Spacer(modifier = Modifier.height(10.dp))

            FlowRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                actions.forEach { action ->
                    OutlinedButton(
                        onClick = { onActionClick(action) },
                        shape = RoundedCornerShape(10.dp),
                        colors = ButtonDefaults.outlinedButtonColors(
                            contentColor = BrandDark
                        ),
                        modifier = Modifier.testTag("board_action_${action.id}")
                    ) {
                        Text(
                            text = action.label ?: action.id ?: "اقدام",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }
            }
        }
    }
}
