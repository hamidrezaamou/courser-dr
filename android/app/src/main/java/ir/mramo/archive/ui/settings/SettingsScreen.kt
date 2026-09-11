package ir.mramo.archive.ui.settings

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.SessionStore
import ir.mramo.archive.data.UserDto
import ir.mramo.archive.ui.components.ArchiveCard
import ir.mramo.archive.ui.components.ArchiveField
import ir.mramo.archive.ui.components.MetaLine
import ir.mramo.archive.ui.components.PrimaryButton
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.Muted
import kotlinx.coroutines.launch

@Composable
fun SettingsScreen(
    repo: ArchiveRepository,
    session: SessionStore,
    user: UserDto?,
    onLoggedOut: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var site by remember { mutableStateOf(SessionStore.DEFAULT_SITE) }
    var saved by remember { mutableStateOf(false) }

    androidx.compose.runtime.LaunchedEffect(Unit) {
        site = session.currentSite()
    }

    Column(
        Modifier
            .fillMaxSize()
            .padding(16.dp),
    ) {
        Text("حساب و اتصال", color = BrandDark, fontWeight = FontWeight.Bold, style = androidx.compose.material3.MaterialTheme.typography.headlineMedium)
        Spacer(Modifier.height(12.dp))
        ArchiveCard {
            Text(user?.name ?: "کاربر", fontWeight = FontWeight.SemiBold, color = BrandDark)
            Spacer(Modifier.height(4.dp))
            MetaLine(listOf(user?.roleLabel, user?.nationalCode, user?.mobile))
        }
        Spacer(Modifier.height(14.dp))
        ArchiveField(site, { site = it; saved = false }, "آدرس هاست سایت")
        Spacer(Modifier.height(10.dp))
        PrimaryButton(if (saved) "ذخیره شد" else "ذخیره آدرس") {
            scope.launch {
                session.saveSite(site)
                saved = true
            }
        }
        Spacer(Modifier.height(10.dp))
        Text("اپ فقط به همین هاست وصل می‌شود. بعد از عوض کردن آدرس یک‌بار خارج شوید و دوباره وارد شوید.", color = Muted)
        Spacer(Modifier.height(24.dp))
        PrimaryButton("خروج از حساب") {
            scope.launch {
                repo.logout()
                onLoggedOut()
            }
        }
    }
}
