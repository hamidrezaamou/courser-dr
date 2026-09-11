package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.theme.*

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun AccountScreen(
    viewModel: AppViewModel,
    modifier: Modifier = Modifier
) {
    val authState by viewModel.authUiState.collectAsState()
    val user = authState.user
    val clinic = authState.clinic

    var showLogoutConfirm by remember { mutableStateOf(false) }
    var serverUrlInput by remember(authState.baseUrl) { mutableStateOf(authState.baseUrl) }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            ClinicTopBar(
                title = "حساب کاربری",
                subtitle = "تنظیمات و اطلاعات کاربری"
            )
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // User Card
            ClinicCard(modifier = Modifier.fillMaxWidth()) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    val userInitial = user?.name?.trim()?.firstOrNull()?.toString() ?: "ک"
                    Box(
                        modifier = Modifier
                            .size(54.dp)
                            .clip(CircleShape)
                            .background(BrandPrimary)
                            .shadow(elevation = 2.dp, shape = CircleShape, spotColor = Color(0x334F86BE)),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = userInitial,
                            fontSize = 20.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                    }

                    Spacer(modifier = Modifier.width(14.dp))

                    Column {
                        Text(
                            text = user?.name ?: "کاربر سامانه",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandInk
                        )
                        Spacer(modifier = Modifier.height(2.dp))
                        Text(
                            text = user?.roleLabel ?: (if (user?.isStaff == true) "کادر درمانگاه" else "بیمار"),
                            fontSize = 12.sp,
                            color = BrandDark,
                            fontWeight = FontWeight.Medium
                        )
                        if (!user?.nationalCode.isNullOrBlank()) {
                            Text(
                                text = "کد ملی: ${toPersianDigits(user?.nationalCode)}",
                                fontSize = 11.sp,
                                color = BrandInk.copy(alpha = 0.5f)
                            )
                        }
                    }
                }

                // Permissions badges
                Spacer(modifier = Modifier.height(14.dp))
                HorizontalDivider(color = SoftBorder, thickness = 0.8.dp)
                Spacer(modifier = Modifier.height(10.dp))

                Text(
                    text = "دسترسی‌های فعال:",
                    fontSize = 12.sp,
                    color = TextSecondary,
                    fontWeight = FontWeight.Medium
                )
                Spacer(modifier = Modifier.height(6.dp))
                FlowRow(
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    verticalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    if (user?.isStaff == true) {
                        Surface(shape = RoundedCornerShape(8.dp), color = Color(0xFFE8F7F3)) {
                            Text("پرسنل درمانگاه", color = BrandMint, fontSize = 11.sp, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                        }
                    }
                    if (user?.canManageClinical == true) {
                        Surface(shape = RoundedCornerShape(8.dp), color = Color(0xFFF1EFFF)) {
                            Text("مدیریت بالینی و معاینه", color = Color(0xFF6C5CE7), fontSize = 11.sp, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                        }
                    }
                    if (user?.canEditPatient == true) {
                        Surface(shape = RoundedCornerShape(8.dp), color = BrandSoft) {
                            Text("ویرایش پرونده بیمار", color = BrandPrimary, fontSize = 11.sp, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                        }
                    }
                }
            }

            // Clinic Info Card
            if (clinic != null) {
                ClinicCard(modifier = Modifier.fillMaxWidth()) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(imageVector = Icons.Default.LocalHospital, contentDescription = null, tint = BrandPrimary, modifier = Modifier.size(20.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "اطلاعات درمانگاه",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = BrandInk
                        )
                    }

                    Spacer(modifier = Modifier.height(10.dp))
                    Text(text = "نام درمانگاه: ${clinic.name ?: "—"}", fontSize = 13.sp, color = TextSecondary)
                    if (!clinic.doctorName.isNullOrBlank()) {
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(text = "پزشک مسئول: ${clinic.doctorName}", fontSize = 13.sp, color = TextSecondary)
                    }
                    if (!clinic.phone.isNullOrBlank()) {
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(text = "تلفن تماس: ${toPersianDigits(clinic.phone)}", fontSize = 13.sp, color = TextSecondary)
                    }
                }
            }

            // Server Settings Card
            ClinicCard(modifier = Modifier.fillMaxWidth()) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(imageVector = Icons.Default.Dns, contentDescription = null, tint = BrandDark, modifier = Modifier.size(20.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = "تنظیمات آدرس سرور",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "آدرس هاست میزبان وب‌سرویس درمانگاه (پیش‌فرض: https://mramo.ir)",
                    fontSize = 12.sp,
                    color = TextMuted
                )

                Spacer(modifier = Modifier.height(10.dp))
                OutlinedTextField(
                    value = serverUrlInput,
                    onValueChange = { serverUrlInput = it },
                    label = { Text("آدرس سرور API") },
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("account_server_url_input"),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = BrandPrimary,
                        unfocusedBorderColor = SoftBorder
                    )
                )

                Spacer(modifier = Modifier.height(10.dp))
                Button(
                    onClick = { viewModel.updateBaseUrl(serverUrlInput) },
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = BrandDark),
                    modifier = Modifier
                        .fillMaxWidth()
                        .testTag("account_save_url_button")
                ) {
                    Text("ذخیره آدرس سرور", fontSize = 13.sp)
                }
            }

            // Logout Button
            Button(
                onClick = { showLogoutConfirm = true },
                shape = RoundedCornerShape(16.dp),
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFFDECEE)),
                modifier = Modifier
                    .fillMaxWidth()
                    .height(50.dp)
                    .testTag("account_logout_button")
            ) {
                Icon(
                    imageVector = Icons.Default.Logout,
                    contentDescription = null,
                    tint = StatusCancelled,
                    modifier = Modifier.size(20.dp)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    text = "خروج از حساب کاربری",
                    color = StatusCancelled,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }

    if (showLogoutConfirm) {
        PersianConfirmDialog(
            title = "خروج از سامانه",
            message = "آیا برای خروج از حساب کاربری خود در سامانه آرشیو درمانگاه اطمینان دارید؟",
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
