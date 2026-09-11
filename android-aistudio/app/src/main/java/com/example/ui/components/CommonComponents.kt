package com.example.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.ui.theme.*

fun toPersianDigits(input: String?): String {
    if (input == null) return ""
    return input.map { char ->
        when (char) {
            '0' -> '۰'
            '1' -> '۱'
            '2' -> '۲'
            '3' -> '۳'
            '4' -> '۴'
            '5' -> '۵'
            '6' -> '۶'
            '7' -> '۷'
            '8' -> '۸'
            '9' -> '۹'
            else -> char
        }
    }.joinToString("")
}

@Composable
fun StatusBadge(
    status: String?,
    label: String?,
    modifier: Modifier = Modifier
) {
    val effectiveStatus = status?.lowercase() ?: ""
    val (bgColor, textColor) = when {
        effectiveStatus.contains("confirm") || effectiveStatus == "تایید" || effectiveStatus == "تأیید شده" ->
            Pair(StatusConfirmed, Color.White)
        effectiveStatus.contains("wait") || effectiveStatus.contains("pending") || effectiveStatus == "انتظار" ->
            Pair(StatusWaiting, Color.White)
        effectiveStatus.contains("done") || effectiveStatus.contains("complete") || effectiveStatus == "تکمیل" || effectiveStatus == "تکمیل شده" ->
            Pair(StatusCompleted, Color.White)
        effectiveStatus.contains("cancel") || effectiveStatus.contains("reject") || effectiveStatus == "لغو" ->
            Pair(StatusCancelled, Color.White)
        else ->
            Pair(BrandSoft, BrandPrimary)
    }

    val displayLabel = label?.takeIf { it.isNotBlank() } ?: when {
        effectiveStatus.contains("confirm") -> "تایید شده"
        effectiveStatus.contains("wait") -> "در انتظار"
        effectiveStatus.contains("done") || effectiveStatus.contains("complete") -> "تکمیل شده"
        effectiveStatus.contains("cancel") -> "لغو شده"
        else -> status ?: "نامشخص"
    }

    Surface(
        modifier = modifier.clip(RoundedCornerShape(8.dp)),
        color = bgColor
    ) {
        Text(
            text = displayLabel,
            color = textColor,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
        )
    }
}

@Composable
fun InitialsAvatar(
    name: String?,
    modifier: Modifier = Modifier,
    size: androidx.compose.ui.unit.Dp = 40.dp,
    shape: androidx.compose.ui.graphics.Shape = RoundedCornerShape(12.dp),
    backgroundColor: Color = BrandSoft,
    textColor: Color = BrandPrimary
) {
    val initials = remember(name) {
        if (name.isNullOrBlank()) {
            "ب"
        } else {
            val parts = name.trim().split("\\s+".toRegex()).filter { it.isNotBlank() }
            if (parts.size >= 2) {
                "${parts[0].firstOrNull() ?: ""}.${parts[1].firstOrNull() ?: ""}"
            } else {
                parts.firstOrNull()?.take(2) ?: "ب"
            }
        }
    }

    Box(
        modifier = modifier
            .size(size)
            .clip(shape)
            .background(backgroundColor),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = initials,
            color = textColor,
            fontSize = if (size > 44.dp) 16.sp else 13.sp,
            fontWeight = FontWeight.Bold
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ClinicTopBar(
    title: String,
    subtitle: String? = null,
    onBack: (() -> Unit)? = null,
    onRefresh: (() -> Unit)? = null,
    modifier: Modifier = Modifier,
    actions: @Composable RowScope.() -> Unit = {}
) {
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp))
            .border(1.dp, DividerColor, RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp))
            .shadow(elevation = 1.dp, shape = RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp), spotColor = Color(0x0F2A425A)),
        color = Color.White
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .statusBarsPadding()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.weight(1f)
            ) {
                if (onBack != null) {
                    IconButton(
                        onClick = onBack,
                        modifier = Modifier
                            .padding(end = 8.dp)
                            .testTag("nav_back_button")
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "بازگشت",
                            tint = BrandDark
                        )
                    }
                }

                Column {
                    Text(
                        text = title,
                        fontSize = 17.sp,
                        fontWeight = FontWeight.Bold,
                        color = BrandDark,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    if (!subtitle.isNullOrBlank()) {
                        Text(
                            text = subtitle,
                            fontSize = 12.sp,
                            color = TextSecondary,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                }
            }

            Row(verticalAlignment = Alignment.CenterVertically) {
                if (onRefresh != null) {
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(CircleShape)
                            .background(BrandSoft)
                            .border(1.dp, SubtleBorder, CircleShape)
                            .clickable(onClick = onRefresh)
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
                actions()
            }
        }
    }
}

@Composable
fun ClinicCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    backgroundColor: Color = CardBackground,
    borderColor: Color = SoftBorder,
    content: @Composable ColumnScope.() -> Unit
) {
    val shape = RoundedCornerShape(20.dp)
    val cardModifier = if (onClick != null) {
        modifier
            .clip(shape)
            .clickable(onClick = onClick)
            .border(1.dp, borderColor, shape)
            .shadow(elevation = 1.dp, shape = shape, spotColor = Color(0x0F2A425A))
            .background(backgroundColor)
            .padding(14.dp)
    } else {
        modifier
            .clip(shape)
            .border(1.dp, borderColor, shape)
            .shadow(elevation = 1.dp, shape = shape, spotColor = Color(0x0F2A425A))
            .background(backgroundColor)
            .padding(14.dp)
    }

    Column(modifier = cardModifier, content = content)
}

@Composable
fun PersianEmptyState(
    message: String,
    icon: ImageVector,
    modifier: Modifier = Modifier,
    actionLabel: String? = null,
    onActionClick: (() -> Unit)? = null
) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Box(
            modifier = Modifier
                .size(72.dp)
                .clip(CircleShape)
                .background(BrandSoft),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = BrandPrimary,
                modifier = Modifier.size(36.dp)
            )
        }
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            text = message,
            fontSize = 15.sp,
            color = TextSecondary,
            textAlign = TextAlign.Center,
            lineHeight = 22.sp
        )
        if (!actionLabel.isNullOrBlank() && onActionClick != null) {
            Spacer(modifier = Modifier.height(16.dp))
            Button(
                onClick = onActionClick,
                colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text(text = actionLabel, fontSize = 14.sp)
            }
        }
    }
}

@Composable
fun PersianConfirmDialog(
    title: String,
    message: String,
    confirmLabel: String = "تأیید",
    dismissLabel: String = "انصراف",
    isDestructive: Boolean = false,
    onConfirm: () -> Unit,
    onDismiss: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(
                text = title,
                fontSize = 17.sp,
                fontWeight = FontWeight.Bold,
                color = BrandInk
            )
        },
        text = {
            Text(
                text = message,
                fontSize = 14.sp,
                color = TextSecondary,
                lineHeight = 22.sp
            )
        },
        confirmButton = {
            Button(
                onClick = onConfirm,
                colors = ButtonDefaults.buttonColors(
                    containerColor = if (isDestructive) StatusCancelled else BrandPrimary
                ),
                shape = RoundedCornerShape(10.dp),
                modifier = Modifier.testTag("dialog_confirm_button")
            ) {
                Text(text = confirmLabel)
            }
        },
        dismissButton = {
            OutlinedButton(
                onClick = onDismiss,
                shape = RoundedCornerShape(10.dp),
                modifier = Modifier.testTag("dialog_dismiss_button")
            ) {
                Text(text = dismissLabel, color = TextSecondary)
            }
        },
        shape = RoundedCornerShape(20.dp),
        containerColor = Color.White
    )
}
