package com.example.ui.web

import android.net.Uri

object SiteUrls {
    const val DEFAULT_ORIGIN = "https://mramo.ir"
    const val APP_UA = "PatientArchiveApp/1.0"

    fun origin(apiOrSite: String): String {
        var value = apiOrSite.trim()
        if (value.isBlank()) return DEFAULT_ORIGIN
        if (!value.startsWith("http://") && !value.startsWith("https://")) {
            value = "https://$value"
        }
        val cut = value.substringBefore("/api").trimEnd('/')
        return cut.ifBlank { DEFAULT_ORIGIN }
    }

    fun page(origin: String, path: String): String {
        val root = origin.trimEnd('/')
        val cleanPath = if (path.startsWith("/")) path else "/$path"
        return "$root$cleanPath"
    }

    fun isSiteHost(origin: String, url: String): Boolean {
        return try {
            val host = Uri.parse(url).host ?: return false
            val siteHost = Uri.parse(origin).host ?: return false
            host.equals(siteHost, ignoreCase = true) || host.endsWith(".$siteHost")
        } catch (_: Exception) {
            false
        }
    }

    fun shouldKeepInWebView(origin: String, url: String): Boolean {
        if (url.startsWith("javascript:", true) || url.startsWith("about:") ||
            url.startsWith("blob:") || url.startsWith("data:")
        ) {
            return true
        }
        if (!url.startsWith("http://") && !url.startsWith("https://")) {
            return false
        }
        return isSiteHost(origin, url)
    }
}
