package com.example.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.unit.LayoutDirection

private val ClinicLightColorScheme = lightColorScheme(
  primary = BrandPrimary,
  onPrimary = Color.White,
  primaryContainer = BrandSoft,
  onPrimaryContainer = BrandDark,
  secondary = BrandMint,
  onSecondary = Color.White,
  secondaryContainer = Color(0xFFD8F3DC),
  onSecondaryContainer = Color(0xFF134E48),
  tertiary = BrandDark,
  onTertiary = Color.White,
  background = MedicalBackground,
  onBackground = BrandInk,
  surface = CardBackground,
  onSurface = BrandInk,
  surfaceVariant = BrandSurface,
  onSurfaceVariant = TextSecondary,
  outline = SoftBorder,
  outlineVariant = DividerColor,
  error = StatusCancelled,
  onError = Color.White
)

private val ClinicDarkColorScheme = darkColorScheme(
  primary = Color(0xFF7CAEE0),
  onPrimary = Color(0xFF0F2C46),
  primaryContainer = BrandDark,
  onPrimaryContainer = BrandSoft,
  secondary = BrandMint,
  onSecondary = Color.White,
  background = Color(0xFF141F2B),
  onBackground = Color(0xFFEDF5FC),
  surface = Color(0xFF1C2B3C),
  onSurface = Color(0xFFEDF5FC),
  surfaceVariant = Color(0xFF22354A),
  onSurfaceVariant = Color(0xFFB0C4DE),
  outline = Color(0xFF384F66),
  outlineVariant = Color(0xFF263A50)
)

@Composable
fun PatientArchiveTheme(
  darkTheme: Boolean = isSystemInDarkTheme(),
  content: @Composable () -> Unit
) {
  val colorScheme = if (darkTheme) ClinicDarkColorScheme else ClinicLightColorScheme

  // Ensure whole app UI is Persian RTL layout
  CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) {
    MaterialTheme(
      colorScheme = colorScheme,
      typography = Typography,
      content = content
    )
  }
}

// Keep alias for compatibility with template preview / existing tests
@Composable
fun MyApplicationTheme(
  darkTheme: Boolean = isSystemInDarkTheme(),
  dynamicColor: Boolean = false,
  content: @Composable () -> Unit
) {
  PatientArchiveTheme(darkTheme = darkTheme, content = content)
}
