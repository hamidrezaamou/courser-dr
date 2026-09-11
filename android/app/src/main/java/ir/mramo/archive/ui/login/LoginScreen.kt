package ir.mramo.archive.ui.login

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Visibility
import androidx.compose.material.icons.outlined.VisibilityOff
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
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
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import ir.mramo.archive.data.ApiException
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.SessionStore
import ir.mramo.archive.data.UserDto
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.ErrorBanner
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.Muted
import ir.mramo.archive.ui.theme.Surface
import kotlinx.coroutines.launch

@Composable
fun LoginScreen(
    repo: ArchiveRepository,
    session: SessionStore,
    onLoggedIn: (UserDto) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var site by remember { mutableStateOf(SessionStore.DEFAULT_SITE) }
    var national by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var hidePass by remember { mutableStateOf(true) }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        site = session.currentSite()
    }

    Column(
        Modifier
            .fillMaxSize()
            .background(Surface)
            .imePadding()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 22.dp, vertical = 36.dp),
        verticalArrangement = Arrangement.Center,
    ) {
        Text("آرشیو بیمار", color = BrandDark, style = MaterialTheme.typography.displaySmall, fontWeight = FontWeight.Bold)
        Spacer(Modifier.height(8.dp))
        Text("ورود کارکنان و بیمار با همان حساب وب‌سایت", color = Muted, style = MaterialTheme.typography.bodyLarge)
        Spacer(Modifier.height(28.dp))
        ErrorBanner(error)
        Spacer(Modifier.height(12.dp))
        ArchiveField(national, { national = it }, "کد ملی")
        Spacer(Modifier.height(12.dp))
        ArchiveField(
            value = password,
            onValueChange = { password = it },
            label = "رمز عبور",
            visualTransformation = if (hidePass) PasswordVisualTransformation() else VisualTransformation.None,
            trailing = {
                IconButton(onClick = { hidePass = !hidePass }) {
                    Icon(
                        imageVector = if (hidePass) Icons.Outlined.Visibility else Icons.Outlined.VisibilityOff,
                        contentDescription = null,
                    )
                }
            },
        )
        Spacer(Modifier.height(12.dp))
        ArchiveField(site, { site = it }, "آدرس سایت (هاست)")
        Spacer(Modifier.height(22.dp))
        PrimaryButton("ورود به سامانه", loading = loading) {
            error = null
            loading = true
            scope.launch {
                try {
                    val logged = repo.login(national, password, site)
                    onLoggedIn(logged)
                } catch (e: ApiException) {
                    error = e.message
                } finally {
                    loading = false
                }
            }
        }
        Spacer(Modifier.height(16.dp))
        Text(
            "بعد از ورود، نوبت‌ها، پرونده‌ها و بُرد روزانه مستقیم از هاست خوانده می‌شود.",
            color = Muted,
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}
