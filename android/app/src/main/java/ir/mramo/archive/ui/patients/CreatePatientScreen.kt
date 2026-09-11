package ir.mramo.archive.ui.patients

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.rounded.ArrowBack
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import ir.mramo.archive.data.ApiException
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.CreatePatientBody
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CreatePatientScreen(
    repo: ArchiveRepository,
    onBack: () -> Unit,
    onCreated: (Long) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var name by remember { mutableStateOf("") }
    var national by remember { mutableStateOf("") }
    var mobile by remember { mutableStateOf("") }
    var age by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    Scaffold(
        containerColor = Surface,
        topBar = {
            TopAppBar(
                title = { Text("ثبت بیمار جدید") },
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
            Spacer(Modifier.height(8.dp))
            ArchiveField(name, { name = it }, "نام و نام خانوادگی")
            Spacer(Modifier.height(10.dp))
            ArchiveField(national, { national = it.filter(Char::isDigit).take(10) }, "کد ملی")
            Spacer(Modifier.height(10.dp))
            ArchiveField(mobile, { mobile = it.filter(Char::isDigit).take(11) }, "موبایل")
            Spacer(Modifier.height(10.dp))
            ArchiveField(age, { age = it }, "سن (اختیاری)")
            Spacer(Modifier.height(20.dp))
            PrimaryButton("ثبت پرونده", loading) {
                error = null
                loading = true
                scope.launch {
                    try {
                        val res = repo.createPatient(
                            CreatePatientBody(
                                name = name.trim(),
                                nationalCode = national,
                                mobile = mobile,
                                age = age.ifBlank { null },
                            ),
                        )
                        val id = res.patient?.id
                        if (id != null) onCreated(id) else error = res.message ?: "ثبت نشد"
                    } catch (e: ApiException) {
                        error = e.message
                    } finally {
                        loading = false
                    }
                }
            }
        }
    }
}
