package com.example

import android.os.Bundle
import com.example.alerts.FollowUpAlerts

class ClinicApp : android.app.Application() {
    override fun onCreate() {
        super.onCreate()
        FollowUpAlerts.ensureChannel(this)
        FollowUpAlerts.scheduleDaily(this)
    }
}
