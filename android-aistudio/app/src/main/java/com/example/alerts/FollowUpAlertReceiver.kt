package com.example.alerts

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

class FollowUpAlertReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent?) {
        if (intent?.action != FollowUpAlerts.ACTION) return
        val pending = goAsync()
        Thread {
            try {
                FollowUpAlerts.checkAndNotify(context.applicationContext, fromAlarm = true)
            } finally {
                FollowUpAlerts.scheduleDaily(context.applicationContext)
                pending.finish()
            }
        }.start()
    }
}
