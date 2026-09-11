package com.example

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.Modifier
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.alerts.FollowUpAlerts
import com.example.ui.AppViewModel
import com.example.ui.screens.ClinicWebScreen
import com.example.ui.theme.PatientArchiveTheme

class MainActivity : ComponentActivity() {
    private var openPath by mutableStateOf<String?>(null)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        openPath = intent.getStringExtra(FollowUpAlerts.EXTRA_OPEN_PATH)
        askNotificationPermission()
        FollowUpAlerts.ensureChannel(this)
        FollowUpAlerts.scheduleDaily(this)
        enableEdgeToEdge()
        setContent {
            PatientArchiveTheme {
                val viewModel: AppViewModel = viewModel()
                ClinicWebScreen(
                    viewModel = viewModel,
                    openPath = openPath
                )
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        openPath = intent.getStringExtra(FollowUpAlerts.EXTRA_OPEN_PATH)
    }

    private fun askNotificationPermission() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) return
        val granted = ContextCompat.checkSelfPermission(
            this,
            Manifest.permission.POST_NOTIFICATIONS
        ) == PackageManager.PERMISSION_GRANTED
        if (!granted) {
            ActivityCompat.requestPermissions(
                this,
                arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                1101
            )
        }
    }
}

@Composable
fun Greeting(name: String, modifier: Modifier = Modifier) {
    Text(text = "Hello $name!", modifier = modifier)
}
