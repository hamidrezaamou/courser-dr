package ir.mramo.archive.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Scheme = lightColorScheme(
    primary = Brand,
    onPrimary = Color.White,
    primaryContainer = BrandSoft,
    onPrimaryContainer = BrandDark,
    secondary = Mint,
    onSecondary = Color.White,
    tertiary = Warm,
    background = Surface,
    onBackground = Ink,
    surface = Panel,
    onSurface = Ink,
    surfaceVariant = BrandSoft,
    onSurfaceVariant = Muted,
    outline = Line,
    error = Danger,
)

@Composable
fun ArchiveTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = Scheme,
        typography = ArchiveTypography,
        content = content,
    )
}
