package ir.mramo.archive.ui.patients

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.rounded.ArrowBack
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
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
import ir.mramo.archive.data.ExamBody
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ExamScreen(
    repo: ArchiveRepository,
    patientId: Long,
    onBack: () -> Unit,
    onSaved: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var examination by remember { mutableStateOf("") }
    var diagnosis by remember { mutableStateOf("") }
    var treatment by remember { mutableStateOf("") }
    var next by remember { mutableStateOf("") }
    var vaR by remember { mutableStateOf("") }
    var vaL by remember { mutableStateOf("") }
    var iopR by remember { mutableStateOf("") }
    var iopL by remember { mutableStateOf("") }
    var eye by remember { mutableStateOf("OU") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    Scaffold(
        containerColor = Surface,
        topBar = {
            TopAppBar(
                title = { Text("ثبت معاینه") },
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
            Text("چشم")
            Spacer(Modifier.height(6.dp))
            Row {
                listOf("OD" to "راست", "OS" to "چپ", "OU" to "هر دو").forEach { (value, label) ->
                    FilterChip(
                        selected = eye == value,
                        onClick = { eye = value },
                        label = { Text(label) },
                        modifier = Modifier.padding(end = 6.dp),
                    )
                }
            }
            Spacer(Modifier.height(10.dp))
            ArchiveField(examination, { examination = it }, "معاینه", singleLine = false)
            Spacer(Modifier.height(10.dp))
            ArchiveField(diagnosis, { diagnosis = it }, "تشخیص", singleLine = false)
            Spacer(Modifier.height(10.dp))
            ArchiveField(treatment, { treatment = it }, "درمان", singleLine = false)
            Spacer(Modifier.height(10.dp))
            ArchiveField(next, { next = it }, "توصیه بعدی")
            Spacer(Modifier.height(10.dp))
            ArchiveField(vaR, { vaR = it }, "حدت بینایی راست")
            Spacer(Modifier.height(10.dp))
            ArchiveField(vaL, { vaL = it }, "حدت بینایی چپ")
            Spacer(Modifier.height(10.dp))
            ArchiveField(iopR, { iopR = it }, "فشار چشم راست")
            Spacer(Modifier.height(10.dp))
            ArchiveField(iopL, { iopL = it }, "فشار چشم چپ")
            Spacer(Modifier.height(20.dp))
            PrimaryButton("ذخیره معاینه", loading) {
                error = null
                loading = true
                scope.launch {
                    try {
                        repo.storeExam(
                            patientId,
                            ExamBody(
                                examination = examination.ifBlank { null },
                                diagnosis = diagnosis.ifBlank { null },
                                treatment = treatment.ifBlank { null },
                                nextInstruction = next.ifBlank { null },
                                eyeSide = eye,
                                vaRight = vaR.ifBlank { null },
                                vaLeft = vaL.ifBlank { null },
                                iopRight = iopR.ifBlank { null },
                                iopLeft = iopL.ifBlank { null },
                            ),
                        )
                        onSaved()
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
