package com.example.alerts

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

class FollowUpBootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent?) {
        val action = intent?.action ?: return
        if (action != Intent.ACTION_BOOT_COMPLETED &&
            action != Intent.ACTION_MY_PACKAGE_REPLACED &&
            action != Intent.ACTION_LOCKED_BOOT_COMPLETED
        ) {
            return
        }
        FollowUpAlerts.scheduleDaily(context.applicationContext)
    }
}
