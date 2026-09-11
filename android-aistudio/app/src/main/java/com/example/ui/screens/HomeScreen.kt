package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.data.model.TodaySurgery
import com.example.data.model.TodayVisit
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.theme.*

@Composable
fun HomeScreen(
    viewModel: AppViewModel,
    onNavigateToPatient: (Long) -> Unit,
    modifier: Modifier = Modifier
) {
    val authState by viewModel.authUiState.collectAsState()
    val homeState by viewModel.homeUiState.collectAsState()

    val user = authState.user
    val clinic = authState.clinic ?: homeState.homeData?.clinic
    val homeData = homeState.homeData

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            // Clean Utility / Minimal Header
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp))
                    .border(1.dp, DividerColor, RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp))
                    .shadow(elevation = 2.dp, shape = RoundedCornerShape(bottomStart = 32.dp, bottomEnd = 32.dp), spotColor = Color(0x0F2A425A)),
                color = Color.White
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .statusBarsPadding()
                        .padding(horizontal = 16.dp, vertical = 16.dp)
                ) {
                    // Top row: Doctor / Clinic profile & Refresh action
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            // Avatar circle 48x48 bg-[#4F86BE]
                            val initialChar = clinic?.doctorName?.trim()?.firstOrNull()?.toString()
                                ?: clinic?.name?.trim()?.firstOrNull()?.toString()
                                ?: "آ"
                            Box(
                                modifier = Modifier
                                    .size(48.dp)
                                    .clip(CircleShape)
                                    .background(BrandPrimary)
                                    .shadow(elevation = 3.dp, shape = CircleShape, spotColor = Color(0x334F86BE)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = initialChar,
                                    fontSize = 18.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color.White
                                )
                            }

                            Column {
                                Text(
                                    text = clinic?.name ?: "آرشیو بیمار",
                                    fontSize = 13.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = BrandInk.copy(alpha = 0.7f)
                                )
                                Text(
                                    text = clinic?.doctorName
                                        ?: clinic?.clinicLabel
                                        ?: clinic?.name
                                        ?: "سامانه آرشیو بیمار",
                                    fontSize = 18.sp,
                                    fontWeight = FontWeight.Black,
                                    color = BrandDark
                                )
                            }
                        }

                        // Notification / Refresh button
                        Box(
                            modifier = Modifier
                                .size(40.dp)
                                .clip(CircleShape)
                                .background(BrandSoft)
                                .border(1.dp, SubtleBorder, CircleShape)
                                .clickable { viewModel.loadHome() }
                                .testTag("nav_refresh_button"),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = Icons.Default.Refresh,
                                contentDescription = "به‌روزرسانی",
                                tint = BrandPrimary,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(14.dp))

                    // Bottom row: Greeting, Date, and Active badge
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.Bottom,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                            Text(
                                text = if (!user?.name.isNullOrBlank())
                                    "سلام، ${user?.name}"
                                else
                                    "سلام، وقت بخیر",
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold,
                                color = BrandInk
                            )

                            val jalaliDate = homeData?.todayJalali ?: "امروز"
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Icon(
                                    imageVector = Icons.Default.CalendarToday,
                                    contentDescription = null,
                                    tint = BrandInk.copy(alpha = 0.6f),
                                    modifier = Modifier.size(13.dp)
                                )
                                Text(
                                    text = toPersianDigits(jalaliDate),
                                    fontSize = 12.sp,
                                    color = BrandInk.copy(alpha = 0.6f)
                                )
                            }
                        }

                        // Clinic active pill badge
                        Surface(
                            shape = RoundedCornerShape(50),
                            color = BrandMint.copy(alpha = 0.1f),
                            border = androidx.compose.foundation.BorderStroke(1.dp, BrandMint.copy(alpha = 0.2f))
                        ) {
                            Text(
                                text = "کلینیک فعال است",
                                color = BrandMint,
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp)
                            )
                        }
                    }
                }
            }
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        if (homeState.isLoading && homeData == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = BrandPrimary)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp),
                contentPadding = PaddingValues(vertical = 14.dp)
            ) {
                // Stats Tiles (Grid 2-column)
                item {
                    val stats = homeData?.stats.orEmpty()
                    val visitsCount = stats.firstOrNull { it.key == "visits_today" }?.value
                        ?: homeData?.todayVisits?.size ?: 0
                    val surgeriesCount = stats.firstOrNull { it.key == "surgeries_today" }?.value
                        ?: homeData?.todaySurgeries?.size ?: 0

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        // Visits Stat Card
                        ClinicCard(
                            modifier = Modifier
                                .weight(1f)
                                .testTag("stat_visits_tile")
                        ) {
                            Text(
                                text = "ویزیت‌های امروز",
                                fontSize = 12.sp,
                                color = BrandInk.copy(alpha = 0.6f)
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Row(
                                verticalAlignment = Alignment.Bottom,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Text(
                                    text = toPersianDigits(visitsCount.toString()),
                                    fontSize = 24.sp,
                                    fontWeight = FontWeight.Black,
                                    color = BrandPrimary
                                )
                                Text(
                                    text = "بیمار",
                                    fontSize = 11.sp,
                                    color = BrandInk.copy(alpha = 0.6f),
                                    modifier = Modifier.padding(bottom = 3.dp)
                                )
                            }
                        }

                        // Surgeries Stat Card
                        ClinicCard(
                            modifier = Modifier
                                .weight(1f)
                                .testTag("stat_surgeries_tile")
                        ) {
                            Text(
                                text = "جراحی‌های امروز",
                                fontSize = 12.sp,
                                color = BrandInk.copy(alpha = 0.6f)
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Row(
                                verticalAlignment = Alignment.Bottom,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Text(
                                    text = toPersianDigits(surgeriesCount.toString()),
                                    fontSize = 24.sp,
                                    fontWeight = FontWeight.Black,
                                    color = BrandDark
                                )
                                Text(
                                    text = "مورد",
                                    fontSize = 11.sp,
                                    color = BrandInk.copy(alpha = 0.6f),
                                    modifier = Modifier.padding(bottom = 3.dp)
                                )
                            }
                        }
                    }
                }

                // Today's Visits Section Header
                item {
                    SectionHeader(
                        title = "لیست ویزیت‌ها",
                        count = homeData?.todayVisits?.size ?: 0,
                        icon = Icons.Default.Person
                    )
                }

                val todayVisits = homeData?.todayVisits
                if (todayVisits.isNullOrEmpty()) {
                    item {
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            PersianEmptyState(
                                message = "برای امروز هیچ نوبت ویزیتی ثبت نشده است.",
                                icon = Icons.Default.EventAvailable
                            )
                        }
                    }
                } else {
                    items(todayVisits) { visit ->
                        TodayVisitCard(
                            visit = visit,
                            onClick = { visit.patientId?.let { onNavigateToPatient(it) } }
                        )
                    }
                }

                // Today's Surgeries Section Header
                item {
                    SectionHeader(
                        title = "لیست جراحی‌ها",
                        count = homeData?.todaySurgeries?.size ?: 0,
                        icon = Icons.Default.LocalHospital
                    )
                }

                val todaySurgeries = homeData?.todaySurgeries
                if (todaySurgeries.isNullOrEmpty()) {
                    item {
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            PersianEmptyState(
                                message = "برای امروز هیچ نوبت جراحی برنامه‌ریزی نشده است.",
                                icon = Icons.Default.CheckCircleOutline
                            )
                        }
                    }
                } else {
                    items(todaySurgeries) { surgery ->
                        TodaySurgeryCard(
                            surgery = surgery,
                            onClick = { surgery.patientId?.let { onNavigateToPatient(it) } }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun SectionHeader(
    title: String,
    count: Int,
    icon: androidx.compose.ui.graphics.vector.ImageVector
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 4.dp, vertical = 2.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = BrandPrimary,
                modifier = Modifier.size(18.dp)
            )
            Text(
                text = title,
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                color = BrandInk
            )
        }

        Surface(
            shape = RoundedCornerShape(8.dp),
            color = BrandSoft
        ) {
            Text(
                text = "${toPersianDigits(count.toString())} مورد",
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = BrandDark,
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
            )
        }
    }
}

@Composable
private fun TodayVisitCard(
    visit: TodayVisit,
    onClick: () -> Unit
) {
    ClinicCard(
        onClick = onClick,
        modifier = Modifier
            .fillMaxWidth()
            .testTag("today_visit_item_${visit.id}")
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
                // Initials Avatar: w-10 h-10 rounded-xl bg-[#EDF5FC]
                InitialsAvatar(
                    name = visit.patientName,
                    size = 40.dp
                )

                Column {
                    Text(
                        text = visit.patientName ?: "بیمار بدون نام",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = if (!visit.visitType.isNullOrBlank())
                            "${visit.visitType}"
                        else
                            "نوبت ویزیت عمومی",
                        fontSize = 11.sp,
                        color = BrandInk.copy(alpha = 0.6f)
                    )
                }
            }

            Column(
                horizontalAlignment = Alignment.End,
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                StatusBadge(
                    status = visit.status,
                    label = visit.statusLabel
                )
                Text(
                    text = toPersianDigits(visit.scheduledTimeLabel ?: visit.scheduledTime ?: "--:--"),
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = BrandInk.copy(alpha = 0.7f)
                )
            }
        }
    }
}

@Composable
private fun TodaySurgeryCard(
    surgery: TodaySurgery,
    onClick: () -> Unit
) {
    ClinicCard(
        onClick = onClick,
        modifier = Modifier
            .fillMaxWidth()
            .testTag("today_surgery_item_${surgery.id}")
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
                    name = surgery.patientName,
                    size = 40.dp
                )

                Column {
                    Text(
                        text = surgery.patientName ?: "بیمار بدون نام",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    val hospitalInfo = surgery.hospitalName ?: "بیمارستان"
                    val eyeSideInfo = when (surgery.eyeSide) {
                        "OD" -> " (چشم راست)"
                        "OS" -> " (چشم چپ)"
                        "OU" -> " (هر دو چشم)"
                        else -> ""
                    }
                    Text(
                        text = "$hospitalInfo$eyeSideInfo",
                        fontSize = 11.sp,
                        color = BrandInk.copy(alpha = 0.6f)
                    )
                }
            }

            Column(
                horizontalAlignment = Alignment.End,
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                StatusBadge(
                    status = surgery.status,
                    label = surgery.statusLabel
                )
                Text(
                    text = toPersianDigits(surgery.scheduledTimeLabel ?: surgery.scheduledTime ?: "--:--"),
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = BrandInk.copy(alpha = 0.7f)
                )
            }
        }
    }
}
