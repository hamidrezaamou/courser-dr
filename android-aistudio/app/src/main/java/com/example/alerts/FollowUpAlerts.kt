package com.example.alerts

import android.app.AlarmManager
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.webkit.CookieManager
import android.widget.Toast
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.example.MainActivity
import com.example.ui.web.SiteUrls
import okhttp3.OkHttpClient
import okhttp3.Request
import org.json.JSONObject
import java.util.Calendar
import java.util.concurrent.TimeUnit

object FollowUpAlerts {
    const val ACTION = "com.example.FOLLOWUP_DAILY_ALERT"
    const val EXTRA_OPEN_PATH = "open_path"
    const val CHANNEL_ID = "followup_alerts_loud_v1"
    private const val PREFS = "followup_alerts"
    private const val NOTIFICATION_ID = 4101
    private const val ALARM_REQUEST = 4102
    private const val HOUR = 8
    private const val MINUTE = 0

    @Volatile
    private var lastCheckAt = 0L

    private val http by lazy {
        OkHttpClient.Builder()
            .followRedirects(false)
            .followSslRedirects(false)
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(20, TimeUnit.SECONDS)
            .build()
    }

    fun ensureChannel(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val manager = context.getSystemService(NotificationManager::class.java) ?: return
        if (manager.getNotificationChannel(CHANNEL_ID) != null) return

        val sound = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
            ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val attrs = AudioAttributes.Builder()
            .setUsage(AudioAttributes.USAGE_ALARM)
            .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
            .build()

        val channel = NotificationChannel(
            CHANNEL_ID,
            "هشدار پیگیری بیماران",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            description = "تعداد پیگیری امروز و آینده را با صدا اعلام می‌کند"
            enableVibration(true)
            vibrationPattern = longArrayOf(0, 500, 180, 500, 180, 900)
            setSound(sound, attrs)
            enableLights(true)
            setShowBadge(true)
        }
        manager.createNotificationChannel(channel)
    }

    fun rememberOrigin(context: Context, origin: String) {
        val clean = origin.trim().trimEnd('/')
        if (clean.isBlank()) return
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .edit()
            .putString("origin", clean)
            .apply()
    }

    fun origin(context: Context): String {
        return context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .getString("origin", null)
            ?.takeIf { it.isNotBlank() }
            ?: SiteUrls.DEFAULT_ORIGIN
    }

    fun scheduleDaily(context: Context) {
        val app = context.applicationContext
        ensureChannel(app)
        val alarmManager = app.getSystemService(AlarmManager::class.java) ?: return
        val pending = alarmPending(app)
        val trigger = nextMorningMillis()
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                alarmManager.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, trigger, pending)
            } else {
                @Suppress("DEPRECATION")
                alarmManager.set(AlarmManager.RTC_WAKEUP, trigger, pending)
            }
        } catch (_: Exception) {
            alarmManager.set(AlarmManager.RTC_WAKEUP, trigger, pending)
        }
    }

    fun onStaffPageLoaded(context: Context) {
        val app = context.applicationContext
        scheduleDaily(app)
        val now = System.currentTimeMillis()
        if (now - lastCheckAt < 60_000L) return
        lastCheckAt = now
        Thread {
            checkAndNotify(app, fromAlarm = false)
        }.start()
    }

    fun testNow(context: Context) {
        val app = context.applicationContext
        ensureChannel(app)
        lastCheckAt = 0L
        Thread {
            val counts = fetchCounts(app)
            val today = counts?.today ?: 0
            val upcoming = counts?.upcoming ?: 0
            val overdue = counts?.overdue ?: 0
            showNotification(
                app,
                today = today,
                upcoming = upcoming,
                overdue = overdue,
                isTest = true,
                fetched = counts != null
            )
            Handler(Looper.getMainLooper()).post {
                Toast.makeText(app, "نوتیف تست ارسال شد", Toast.LENGTH_SHORT).show()
            }
        }.start()
    }

    fun checkAndNotify(context: Context, fromAlarm: Boolean) {
        val app = context.applicationContext
        ensureChannel(app)
        val counts = fetchCounts(app) ?: return
        if (counts.today <= 0 && counts.upcoming <= 0 && counts.overdue <= 0) return
        if (!shouldNotify(app, fromAlarm, counts.date, counts.today, counts.upcoming, counts.overdue)) return

        showNotification(app, counts.today, counts.upcoming, counts.overdue)
        rememberShown(app, counts.date, counts.today, counts.upcoming, counts.overdue)
    }

    private data class Counts(
        val today: Int,
        val upcoming: Int,
        val overdue: Int,
        val date: String
    )

    private fun fetchCounts(context: Context): Counts? {
        val origin = origin(context)
        CookieManager.getInstance().flush()
        val cookie = CookieManager.getInstance().getCookie(origin)
        if (cookie.isNullOrBlank()) return null

        val url = SiteUrls.page(origin, "/follow-ups/alert-summary")
        val request = Request.Builder()
            .url(url)
            .header("Cookie", cookie)
            .header("Accept", "application/json")
            .header("X-Requested-With", "XMLHttpRequest")
            .header("User-Agent", "Mozilla/5.0 PatientArchiveApp/1.0")
            .get()
            .build()

        val body = try {
            http.newCall(request).execute().use { response ->
                if (!response.isSuccessful) return null
                val text = response.body?.string().orEmpty().trim()
                if (text.isEmpty() || text.startsWith("<")) return null
                text
            }
        } catch (_: Exception) {
            return null
        }

        val json = try {
            JSONObject(body)
        } catch (_: Exception) {
            return null
        }
        if (!json.optBoolean("ok", false) || !json.optBoolean("available", true)) return null

        return Counts(
            today = json.optInt("today", 0),
            upcoming = json.optInt("upcoming", 0),
            overdue = json.optInt("overdue", 0),
            date = json.optString("date").ifBlank {
                java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.US)
                    .format(java.util.Date())
            }
        )
    }

    private fun shouldNotify(
        context: Context,
        fromAlarm: Boolean,
        date: String,
        today: Int,
        upcoming: Int,
        overdue: Int
    ): Boolean {
        if (fromAlarm) return true
        val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
        val lastDate = prefs.getString("last_date", "").orEmpty()
        val lastToday = prefs.getInt("last_today", -1)
        val lastUpcoming = prefs.getInt("last_upcoming", -1)
        val lastOverdue = prefs.getInt("last_overdue", -1)
        if (lastDate != date) return true
        return today > lastToday || upcoming > lastUpcoming || overdue > lastOverdue
    }

    private fun rememberShown(context: Context, date: String, today: Int, upcoming: Int, overdue: Int) {
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit()
            .putString("last_date", date)
            .putInt("last_today", today)
            .putInt("last_upcoming", upcoming)
            .putInt("last_overdue", overdue)
            .apply()
    }

    private fun showNotification(
        context: Context,
        today: Int,
        upcoming: Int,
        overdue: Int,
        isTest: Boolean = false,
        fetched: Boolean = true
    ) {
        val title = if (isTest) "پیگیری بیماران — تست" else "پیگیری بیماران"
        val lines = mutableListOf<String>()
        if (!fetched && isTest) {
            lines += "نوتیف تست آمد — اگر صدا شنیدی درست است"
        } else {
            if (today > 0) lines += "امروز ${fa(today)} پیگیری دارید — وارد برنامه شوید"
            else lines += "امروز ${fa(today)} پیگیری سررسیدشده"
            lines += "آینده: ${fa(upcoming)} مورد"
            if (overdue > 0) lines += "عقب‌افتاده: ${fa(overdue)} مورد"
        }
        val text = lines.first()
        val big = lines.joinToString("\n")
        val notifyId = if (isTest) NOTIFICATION_ID + 1 else NOTIFICATION_ID

        val open = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or
                Intent.FLAG_ACTIVITY_CLEAR_TOP or
                Intent.FLAG_ACTIVITY_SINGLE_TOP
            putExtra(EXTRA_OPEN_PATH, "/follow-ups?bucket=today")
        }
        val contentIntent = PendingIntent.getActivity(
            context,
            notifyId,
            open,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val sound = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
            ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_popup_reminder)
            .setContentTitle(title)
            .setContentText(text)
            .setStyle(NotificationCompat.BigTextStyle().bigText(big))
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_ALARM)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)
            .setAutoCancel(true)
            .setOnlyAlertOnce(false)
            .setSound(sound)
            .setVibrate(longArrayOf(0, 500, 180, 500, 180, 900))
            .setContentIntent(contentIntent)
            .build()

        try {
            val manager = NotificationManagerCompat.from(context)
            manager.cancel(notifyId)
            manager.notify(notifyId, notification)
        } catch (_: SecurityException) {
        }
    }

    private fun alarmPending(context: Context): PendingIntent {
        val intent = Intent(context, FollowUpAlertReceiver::class.java).setAction(ACTION)
        return PendingIntent.getBroadcast(
            context,
            ALARM_REQUEST,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
    }

    private fun nextMorningMillis(): Long {
        val cal = Calendar.getInstance()
        cal.set(Calendar.HOUR_OF_DAY, HOUR)
        cal.set(Calendar.MINUTE, MINUTE)
        cal.set(Calendar.SECOND, 0)
        cal.set(Calendar.MILLISECOND, 0)
        if (cal.timeInMillis <= System.currentTimeMillis()) {
            cal.add(Calendar.DATE, 1)
        }
        return cal.timeInMillis
    }

    private fun fa(value: Int): String {
        val digits = charArrayOf('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹')
        return value.toString().map { ch ->
            if (ch in '0'..'9') digits[ch - '0'] else ch
        }.joinToString("")
    }
}
