package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
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
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.theme.*

@Composable
fun PatientSelfProfileScreen(
    viewModel: AppViewModel,
    modifier: Modifier = Modifier
) {
    val profileState by viewModel.patientProfileUiState.collectAsState()
    val authState by viewModel.authUiState.collectAsState()

    var showLogoutConfirm by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        viewModel.loadPatientSelfProfile()
    }

    val profile = profileState.profile
    val patient = profile?.patient
    val clinic = profile?.clinic ?: authState.clinic
    val user = profile?.user ?: authState.user

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            ClinicTopBar(
                title = "پرونده من",
                subtitle = clinic?.name ?: "درمانگاه تخصصی",
                onRefresh = { viewModel.loadPatientSelfProfile() },
                actions = {
                    IconButton(
                        onClick = { showLogoutConfirm = true },
                        modifier = Modifier.testTag("patient_self_logout_button")
                    ) {
                        Icon(
                            imageVector = Icons.Default.Logout,
                            contentDescription = "خروج",
                            tint = StatusCancelled
                        )
                    }
                }
            )
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        if (profileState.isLoading && profile == null) {
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
                verticalArrangement = Arrangement.spacedBy(16.dp),
                contentPadding = PaddingValues(vertical = 16.dp)
            ) {
                // Greeting Card
                item {
                    ClinicCard(
                        backgroundColor = BrandDark,
                        borderColor = BrandDark,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(54.dp)
                                    .clip(CircleShape)
                                    .background(Color(0x33FFFFFF)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Person,
                                    contentDescription = null,
                                    tint = Color.White,
                                    modifier = Modifier.size(32.dp)
                                )
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text(
                                    text = "خوش آمدید، ${patient?.name ?: user?.name ?: "بیمار گرامی"}",
                                    fontSize = 17.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color.White
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = "کد ملی: ${toPersianDigits(patient?.nationalCode ?: user?.nationalCode ?: "—")}",
                                    fontSize = 13.sp,
                                    color = BrandSoft
                                )
                            }
                        }
                    }
                }

                // Clinic Contact Info
                if (clinic != null) {
                    item {
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.LocalHospital, contentDescription = null, tint = BrandPrimary, modifier = Modifier.size(20.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(
                                    text = clinic.name ?: "درمانگاه",
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = BrandInk
                                )
                            }
                            if (!clinic.doctorName.isNullOrBlank()) {
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(text = "پزشک: ${clinic.doctorName}", fontSize = 13.sp, color = TextSecondary)
                            }
                            if (!clinic.phone.isNullOrBlank()) {
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(text = "تلفن تماس: ${toPersianDigits(clinic.phone)}", fontSize = 13.sp, color = TextSecondary)
                            }
                        }
                    }
                }

                // Timeline Header
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.History, contentDescription = null, tint = BrandPrimary, modifier = Modifier.size(20.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "سوابق و نوبت‌های شما",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandInk
                        )
                    }
                }

                val timeline = profile?.timeline ?: emptyList()
                if (timeline.isEmpty()) {
                    item {
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            PersianEmptyState(
                                message = "هنوز سابقه‌ای برای پرونده شما ثبت نشده است.",
                                icon = Icons.Default.EventNote
                            )
                        }
                    }
                } else {
                    items(timeline) { item ->
                        ClinicCard(modifier = Modifier.fillMaxWidth()) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        text = item.title ?: "نوبت / ویزیت",
                                        fontSize = 15.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = BrandInk
                                    )
                                    val dateStr = item.date ?: item.createdAt
                                    if (!dateStr.isNullOrBlank()) {
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            text = toPersianDigits(dateStr),
                                            fontSize = 12.sp,
                                            color = TextMuted
                                        )
                                    }
                                    if (!item.description.isNullOrBlank()) {
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text(
                                            text = item.description,
                                            fontSize = 13.sp,
                                            color = TextSecondary
                                        )
                                    }
                                }
                                StatusBadge(status = item.type, label = null)
                            }
                        }
                    }
                }
            }
        }
    }

    if (showLogoutConfirm) {
        PersianConfirmDialog(
            title = "خروج از حساب کاربری",
            message = "آیا قصد خروج از حساب کاربری خود را دارید؟",
            confirmLabel = "خروج",
            dismissLabel = "انصراف",
            isDestructive = true,
            onConfirm = {
                showLogoutConfirm = false
                viewModel.logout()
            },
            onDismiss = { showLogoutConfirm = false }
        )
    }
}
