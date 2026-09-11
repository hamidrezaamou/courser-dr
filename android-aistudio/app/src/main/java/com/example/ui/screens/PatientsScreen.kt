package com.example.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
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
import com.example.data.model.Patient
import com.example.ui.AppViewModel
import com.example.ui.components.*
import com.example.ui.screens.dialogs.CreatePatientDialog
import com.example.ui.theme.*

@Composable
fun PatientsScreen(
    viewModel: AppViewModel,
    onOpenPatientDetail: (Long) -> Unit,
    modifier: Modifier = Modifier
) {
    val patientsState by viewModel.patientsUiState.collectAsState()
    var showCreateDialog by remember { mutableStateOf(false) }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            ClinicTopBar(
                title = "پرونده‌های بیماران",
                subtitle = "جستجو و مدیریت اطلاعات مراجعین",
                onRefresh = { viewModel.loadPatients() }
            )
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = { showCreateDialog = true },
                containerColor = BrandPrimary,
                contentColor = Color.White,
                shape = RoundedCornerShape(18.dp),
                icon = {
                    Icon(
                        imageVector = Icons.Default.PersonAdd,
                        contentDescription = "ثبت بیمار جدید"
                    )
                },
                text = { Text("بیمار جدید", fontWeight = FontWeight.Bold) },
                modifier = Modifier.testTag("fab_create_patient")
            )
        },
        containerColor = MedicalBackground
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // Search field
            OutlinedTextField(
                value = patientsState.query,
                onValueChange = { viewModel.searchPatients(it) },
                placeholder = { Text("جستجوی نام، کد ملی یا شماره همراه...") },
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Default.Search,
                        contentDescription = null,
                        tint = BrandPrimary
                    )
                },
                trailingIcon = {
                    if (patientsState.query.isNotBlank()) {
                        IconButton(onClick = { viewModel.searchPatients("") }) {
                            Icon(
                                imageVector = Icons.Default.Close,
                                contentDescription = "پاک کردن",
                                tint = TextMuted
                            )
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(16.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 12.dp)
                    .testTag("patient_search_input"),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = BrandPrimary,
                    unfocusedBorderColor = SoftBorder,
                    focusedContainerColor = Color.White,
                    unfocusedContainerColor = Color.White
                )
            )

            // Patients List
            if (patientsState.isLoading && patientsState.patients.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .weight(1f),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = BrandPrimary)
                }
            } else if (patientsState.patients.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .weight(1f),
                    contentAlignment = Alignment.Center
                ) {
                    PersianEmptyState(
                        message = if (patientsState.query.isNotBlank())
                            "بیماری با مشخصات «${patientsState.query}» یافت نشد."
                        else
                            "تاکنون پرونده بیماری در سامانه ثبت نشده است.",
                        icon = Icons.Default.FolderOpen,
                        actionLabel = "ثبت اولین بیمار",
                        onActionClick = { showCreateDialog = true }
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .weight(1f)
                        .padding(horizontal = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    contentPadding = PaddingValues(bottom = 80.dp, top = 4.dp)
                ) {
                    items(patientsState.patients) { patient ->
                        PatientItemCard(
                            patient = patient,
                            onClick = {
                                patient.id?.let { onOpenPatientDetail(it) }
                            }
                        )
                    }
                }
            }
        }
    }

    if (showCreateDialog) {
        CreatePatientDialog(
            isCreating = patientsState.isCreating,
            errorMessage = patientsState.errorMessage,
            onDismiss = { showCreateDialog = false },
            onSubmit = { name, nationalCode, mobile, age ->
                viewModel.createPatient(name, nationalCode, mobile, age) {
                    showCreateDialog = false
                }
            }
        )
    }
}

@Composable
private fun PatientItemCard(
    patient: Patient,
    onClick: () -> Unit
) {
    ClinicCard(
        onClick = onClick,
        modifier = Modifier
            .fillMaxWidth()
            .testTag("patient_card_${patient.id}")
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
                    name = patient.name,
                    size = 42.dp
                )

                Column {
                    Text(
                        text = patient.name ?: "بیمار بدون نام",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandInk
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = "کد ملی: ${toPersianDigits(patient.nationalCode ?: "—")}",
                            fontSize = 11.sp,
                            color = BrandInk.copy(alpha = 0.6f)
                        )
                        if (patient.age != null && patient.age.toString().isNotBlank()) {
                            Text(
                                text = "• سن: ${toPersianDigits(patient.age.toString())} سال",
                                fontSize = 11.sp,
                                color = BrandInk.copy(alpha = 0.5f)
                            )
                        }
                    }
                    if (!patient.mobile.isNullOrBlank()) {
                        Text(
                            text = "همراه: ${toPersianDigits(patient.mobile)}",
                            fontSize = 11.sp,
                            color = BrandInk.copy(alpha = 0.6f)
                        )
                    }
                }
            }

            Icon(
                imageVector = Icons.Default.ChevronLeft,
                contentDescription = "مشاهده پرونده",
                tint = BrandPrimary.copy(alpha = 0.8f),
                modifier = Modifier.size(20.dp)
            )
        }
    }
}
