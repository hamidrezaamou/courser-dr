package ir.mramo.archive.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import ir.mramo.archive.ui.theme.Brand
import ir.mramo.archive.ui.theme.BrandDark
import ir.mramo.archive.ui.theme.BrandSoft
import ir.mramo.archive.ui.theme.Ink
import ir.mramo.archive.ui.theme.Line
import ir.mramo.archive.ui.theme.Muted

@Composable
fun ArchiveCard(modifier: Modifier = Modifier, content: @Composable () -> Unit) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(22.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        border = androidx.compose.foundation.BorderStroke(1.dp, Line),
    ) {
        Box(Modifier.padding(16.dp)) { content() }
    }
}

@Composable
fun PrimaryButton(
    text: String,
    loading: Boolean = false,
    enabled: Boolean = true,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
) {
    Button(
        onClick = onClick,
        enabled = enabled && !loading,
        modifier = modifier
            .fillMaxWidth()
            .height(52.dp),
        shape = RoundedCornerShape(16.dp),
        colors = ButtonDefaults.buttonColors(containerColor = Brand, contentColor = Color.White),
    ) {
        if (loading) {
            CircularProgressIndicator(color = Color.White, strokeWidth = 2.dp, modifier = Modifier.size(22.dp))
        } else {
            Text(text, fontWeight = FontWeight.SemiBold)
        }
    }
}

@Composable
fun ArchiveField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    singleLine: Boolean = true,
    visualTransformation: VisualTransformation = VisualTransformation.None,
    trailing: @Composable (() -> Unit)? = null,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier.fillMaxWidth(),
        singleLine = singleLine,
        visualTransformation = visualTransformation,
        trailingIcon = trailing,
        shape = RoundedCornerShape(16.dp),
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = Brand,
            unfocusedBorderColor = Line,
            focusedLabelColor = BrandDark,
        ),
    )
}

@Composable
fun StatusChip(label: String, color: Color) {
    Text(
        text = label,
        color = color,
        style = MaterialTheme.typography.labelLarge,
        modifier = Modifier
            .clip(RoundedCornerShape(999.dp))
            .background(color.copy(alpha = 0.12f))
            .padding(horizontal = 10.dp, vertical = 4.dp),
    )
}

@Composable
fun AvatarBubble(initial: String?, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .size(46.dp)
            .clip(CircleShape)
            .background(BrandSoft),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = (initial ?: "؟").take(1),
            color = BrandDark,
            fontWeight = FontWeight.Bold,
        )
    }
}

@Composable
fun ErrorBanner(message: String?) {
    if (message.isNullOrBlank()) return
    Box(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(Color(0xFFFFE8E5))
            .padding(12.dp),
    ) {
        Text(message, color = Color(0xFFB42318), style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
fun SectionTitle(text: String) {
    Text(text, color = Ink, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
}

@Composable
fun StatTile(label: String, value: String, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier
            .clip(RoundedCornerShape(18.dp))
            .background(BrandSoft)
            .padding(14.dp),
    ) {
        Text(value, color = BrandDark, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.headlineMedium)
        Spacer(Modifier.height(4.dp))
        Text(label, color = Muted, style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
fun RowScope.StatTileFlex(label: String, value: String) {
    StatTile(label, value, modifier = Modifier.weight(1f))
}

@Composable
fun EmptyState(text: String) {
    Column(
        Modifier
            .fillMaxWidth()
            .padding(28.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text(text, color = Muted, style = MaterialTheme.typography.bodyLarge)
    }
}

@Composable
fun LoadingBox() {
    Box(Modifier.fillMaxWidth().padding(40.dp), contentAlignment = Alignment.Center) {
        CircularProgressIndicator(color = Brand)
    }
}

@Composable
fun MetaLine(parts: List<String?>) {
    Text(
        text = parts.filter { !it.isNullOrBlank() }.joinToString("  ·  "),
        color = Muted,
        style = MaterialTheme.typography.bodyMedium,
    )
}
