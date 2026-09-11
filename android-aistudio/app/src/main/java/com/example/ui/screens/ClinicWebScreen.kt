package com.example.ui.screens

import android.annotation.SuppressLint
import android.app.DownloadManager
import android.content.Intent
import android.graphics.Bitmap
import android.net.Uri
import android.os.Environment
import android.view.View
import android.view.ViewGroup
import android.webkit.CookieManager
import android.webkit.URLUtil
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebView.WebViewTransport
import android.webkit.WebViewClient
import androidx.activity.compose.BackHandler
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.WifiOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.example.alerts.FollowUpAlerts
import com.example.ui.AppViewModel
import com.example.ui.theme.*
import com.example.ui.web.SiteUrls

private class ClinicWebHost {
    var view: WebView? = null
    var fileCallback: ValueCallback<Array<Uri>>? = null
    var lastOrigin: String = SiteUrls.DEFAULT_ORIGIN
    var pendingPath: String? = null
}

@OptIn(ExperimentalMaterial3Api::class)
@SuppressLint("SetJavaScriptEnabled")
@Composable
fun ClinicWebScreen(
    viewModel: AppViewModel,
    modifier: Modifier = Modifier,
    openPath: String? = null
) {
    val context = LocalContext.current
    val authState by viewModel.authUiState.collectAsState()
    val origin = remember(authState.baseUrl) { SiteUrls.origin(authState.baseUrl) }
    val host = remember { ClinicWebHost() }

    var progress by remember { mutableIntStateOf(0) }
    var canGoBack by remember { mutableStateOf(false) }
    var loadError by remember { mutableStateOf<String?>(null) }
    var menuOpen by remember { mutableStateOf(false) }
    var lastMenuTap by remember { mutableLongStateOf(0L) }
    var showServerDialog by remember { mutableStateOf(false) }
    var serverInput by remember(origin) { mutableStateOf(origin) }

    val filePicker = rememberLauncherForActivityResult(
        ActivityResultContracts.GetMultipleContents()
    ) { uris ->
        val cb = host.fileCallback
        host.fileCallback = null
        if (uris.isEmpty()) cb?.onReceiveValue(null) else cb?.onReceiveValue(uris.toTypedArray())
    }

    fun reloadHome() {
        loadError = null
        host.view?.loadUrl(SiteUrls.page(origin, "/login"))
    }

    BackHandler(enabled = canGoBack) {
        host.view?.goBack()
    }

    LaunchedEffect(origin) {
        FollowUpAlerts.rememberOrigin(context, origin)
        if (host.lastOrigin != origin && host.view != null) {
            host.lastOrigin = origin
            host.view?.loadUrl(SiteUrls.page(origin, "/login"))
        }
    }

    LaunchedEffect(openPath) {
        val path = openPath?.takeIf { it.isNotBlank() } ?: return@LaunchedEffect
        val view = host.view
        if (view != null) {
            view.loadUrl(SiteUrls.page(origin, path))
        } else {
            host.pendingPath = path
        }
    }

    Box(
        modifier = modifier
            .fillMaxSize()
            .background(MedicalBackground)
            .statusBarsPadding()
    ) {
        AndroidView(
            factory = { ctx ->
                CookieManager.getInstance().setAcceptCookie(true)
                WebView(ctx).apply {
                    layoutParams = ViewGroup.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT
                    )
                    layoutDirection = View.LAYOUT_DIRECTION_LTR
                    setBackgroundColor(0xFFF3F8FD.toInt())
                    CookieManager.getInstance().setAcceptThirdPartyCookies(this, true)

                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    settings.databaseEnabled = true
                    settings.useWideViewPort = true
                    settings.loadWithOverviewMode = true
                    settings.builtInZoomControls = false
                    settings.displayZoomControls = false
                    settings.mediaPlaybackRequiresUserGesture = false
                    settings.mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
                    settings.javaScriptCanOpenWindowsAutomatically = true
                    settings.setSupportMultipleWindows(true)
                    settings.allowFileAccess = true
                    settings.cacheMode = WebSettings.LOAD_DEFAULT
                    settings.userAgentString = settings.userAgentString + " " + SiteUrls.APP_UA
                    isFocusable = true
                    isFocusableInTouchMode = true

                    webViewClient = object : WebViewClient() {
                        override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                            val url = request.url.toString()
                            val currentOrigin = host.lastOrigin
                            if (SiteUrls.shouldKeepInWebView(currentOrigin, url)) {
                                return false
                            }
                            return openExternal(view, url)
                        }

                        override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                            loadError = null
                            canGoBack = view?.canGoBack() == true
                        }

                        override fun onPageFinished(view: WebView, url: String) {
                            CookieManager.getInstance().flush()
                            canGoBack = view.canGoBack()
                            view.evaluateJavascript(
                                "(function(){var b=document.getElementById('pwa-install-banner'); if(b) b.remove();})();",
                                null
                            )
                            if (SiteUrls.isSiteHost(host.lastOrigin, url) &&
                                !url.contains("/login")
                            ) {
                                FollowUpAlerts.onStaffPageLoaded(view.context)
                            }
                        }

                        override fun doUpdateVisitedHistory(view: WebView, url: String?, isReload: Boolean) {
                            canGoBack = view.canGoBack()
                        }

                        override fun onReceivedError(
                            view: WebView,
                            request: WebResourceRequest,
                            error: WebResourceError
                        ) {
                            if (request.isForMainFrame) {
                                loadError = "ارتباط با سایت برقرار نشد. اتصال اینترنت را بررسی کنید."
                            }
                        }
                    }

                    webChromeClient = object : WebChromeClient() {
                        override fun onProgressChanged(view: WebView?, newProgress: Int) {
                            progress = newProgress
                        }

                        override fun onShowFileChooser(
                            webView: WebView?,
                            filePathCallback: ValueCallback<Array<Uri>>?,
                            fileChooserParams: FileChooserParams?
                        ): Boolean {
                            host.fileCallback?.onReceiveValue(null)
                            host.fileCallback = filePathCallback
                            val mime = fileChooserParams?.acceptTypes
                                ?.firstOrNull { !it.isNullOrBlank() && it != "." }
                                ?: "*/*"
                            try {
                                filePicker.launch(if (mime.contains('/')) mime else "*/*")
                            } catch (_: Exception) {
                                filePicker.launch("*/*")
                            }
                            return true
                        }

                        override fun onCreateWindow(
                            view: WebView,
                            isDialog: Boolean,
                            isUserGesture: Boolean,
                            resultMsg: android.os.Message?
                        ): Boolean {
                            val extra = WebView(view.context).apply {
                                webViewClient = object : WebViewClient() {
                                    override fun shouldOverrideUrlLoading(v: WebView, request: WebResourceRequest): Boolean {
                                        val url = request.url.toString()
                                        if (SiteUrls.shouldKeepInWebView(host.lastOrigin, url)) {
                                            view.loadUrl(url)
                                        } else {
                                            openExternal(view, url)
                                        }
                                        return true
                                    }
                                }
                            }
                            val transport = resultMsg?.obj as? WebViewTransport ?: return false
                            transport.webView = extra
                            resultMsg.sendToTarget()
                            return true
                        }
                    }

                    setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
                        try {
                            val request = DownloadManager.Request(Uri.parse(url))
                            request.setMimeType(mimeType)
                            request.addRequestHeader("User-Agent", userAgent)
                            val cookies = CookieManager.getInstance().getCookie(url)
                            if (!cookies.isNullOrBlank()) request.addRequestHeader("Cookie", cookies)
                            request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                            request.setDestinationInExternalPublicDir(
                                Environment.DIRECTORY_DOWNLOADS,
                                URLUtil.guessFileName(url, contentDisposition, mimeType)
                            )
                            ctx.getSystemService(DownloadManager::class.java).enqueue(request)
                        } catch (_: Exception) {
                            openExternal(this, url)
                        }
                    }

                    host.view = this
                    host.lastOrigin = origin
                    val start = host.pendingPath?.also { host.pendingPath = null }
                        ?: openPath?.takeIf { it.isNotBlank() }
                        ?: "/login"
                    loadUrl(SiteUrls.page(origin, start))
                }
            },
            modifier = Modifier
                .fillMaxSize()
                .testTag("clinic_site_webview"),
            update = { webView ->
                host.view = webView
            }
        )

        if (progress in 1..99) {
            LinearProgressIndicator(
                progress = { progress / 100f },
                modifier = Modifier
                    .fillMaxWidth()
                    .align(Alignment.TopCenter),
                color = BrandPrimary,
                trackColor = Color.Transparent
            )
        }

        Box(
            modifier = Modifier
                .align(Alignment.TopStart)
                .padding(6.dp)
        ) {
            IconButton(
                onClick = {
                    val now = System.currentTimeMillis()
                    if (now - lastMenuTap < 600) {
                        lastMenuTap = 0L
                        menuOpen = false
                        FollowUpAlerts.testNow(context)
                    } else {
                        lastMenuTap = now
                        menuOpen = true
                    }
                },
                modifier = Modifier
                    .size(40.dp)
                    .testTag("clinic_overflow_menu")
            ) {
                Icon(Icons.Default.MoreVert, contentDescription = "منو", tint = BrandDark.copy(alpha = 0.55f))
            }
            DropdownMenu(expanded = menuOpen, onDismissRequest = { menuOpen = false }) {
                DropdownMenuItem(
                    text = { Text("بارگذاری مجدد") },
                    leadingIcon = { Icon(Icons.Default.Refresh, contentDescription = null) },
                    onClick = {
                        menuOpen = false
                        loadError = null
                        host.view?.reload()
                    }
                )
                DropdownMenuItem(
                    text = { Text("تست نوتیف پیگیری") },
                    onClick = {
                        menuOpen = false
                        FollowUpAlerts.testNow(context)
                    }
                )
                DropdownMenuItem(
                    text = { Text("آدرس سایت") },
                    onClick = {
                        menuOpen = false
                        serverInput = origin
                        showServerDialog = true
                    }
                )
            }
        }

        if (loadError != null) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .background(MedicalBackground)
                    .padding(24.dp),
                verticalArrangement = Arrangement.Center,
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(Icons.Default.WifiOff, contentDescription = null, tint = BrandPrimary, modifier = Modifier.size(48.dp))
                Spacer(Modifier.height(12.dp))
                Text(loadError ?: "", color = BrandInk, fontWeight = FontWeight.Medium)
                Spacer(Modifier.height(16.dp))
                Button(onClick = { reloadHome() }, colors = ButtonDefaults.buttonColors(containerColor = BrandPrimary)) {
                    Text("تلاش دوباره")
                }
            }
        }
    }

    if (showServerDialog) {
        AlertDialog(
            onDismissRequest = { showServerDialog = false },
            title = { Text("آدرس درمانگاه") },
            text = {
                Column {
                    Text("مثلاً https://mramo.ir", fontSize = 13.sp, color = TextSecondary)
                    Spacer(Modifier.height(10.dp))
                    OutlinedTextField(
                        value = serverInput,
                        onValueChange = { serverInput = it },
                        singleLine = true,
                        label = { Text("آدرس سایت") }
                    )
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.updateBaseUrl(serverInput)
                    showServerDialog = false
                }) { Text("ذخیره") }
            },
            dismissButton = {
                TextButton(onClick = { showServerDialog = false }) { Text("انصراف") }
            }
        )
    }
}

private fun openExternal(view: WebView, url: String): Boolean {
    val uri = Uri.parse(url)
    val scheme = uri.scheme ?: return true
    return try {
        when (scheme) {
            "tel", "mailto", "sms", "geo" -> {
                view.context.startActivity(Intent(Intent.ACTION_VIEW, uri))
                true
            }
            "http", "https" -> {
                view.context.startActivity(Intent(Intent.ACTION_VIEW, uri))
                true
            }
            else -> true
        }
    } catch (_: Exception) {
        true
    }
}
