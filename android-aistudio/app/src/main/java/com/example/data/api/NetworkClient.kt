package com.example.data.api

import com.example.data.local.SessionManager
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import java.util.concurrent.TimeUnit

class NetworkClient(private val sessionManager: SessionManager) {

    val moshi: Moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()

    @Volatile private var cachedToken: String? = null
    @Volatile private var currentBaseUrl: String = SessionManager.DEFAULT_BASE_URL
    private var cachedApiService: ApiService? = null

    init {
        CoroutineScope(Dispatchers.IO).launch {
            sessionManager.tokenFlow.collect { cachedToken = it }
        }
        CoroutineScope(Dispatchers.IO).launch {
            sessionManager.baseUrlFlow.collect { url ->
                if (url.isNotBlank() && url != currentBaseUrl) {
                    currentBaseUrl = url
                    cachedApiService = null
                }
            }
        }
    }

    fun setBaseUrl(url: String) {
        if (url.isBlank()) return
        val normalized = if (url.endsWith("/")) url else "$url/"
        if (normalized != currentBaseUrl) {
            currentBaseUrl = normalized
            cachedApiService = null
        }
    }

    fun setCachedToken(token: String?) {
        cachedToken = token
    }

    private val authInterceptor = Interceptor { chain ->
        val original = chain.request()
        val token = cachedToken

        val requestBuilder = original.newBuilder()
            .header("Accept", "application/json")

        if (!token.isNullOrBlank()) {
            requestBuilder.header("Authorization", "Bearer $token")
        }

        val response = chain.proceed(requestBuilder.build())

        if (response.code == 401) {
            cachedToken = null
            CoroutineScope(Dispatchers.IO).launch {
                sessionManager.clearSession()
            }
        }

        response
    }

    private val okHttpClient: OkHttpClient by lazy {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.HEADERS
        }

        OkHttpClient.Builder()
            .connectTimeout(25, TimeUnit.SECONDS)
            .readTimeout(25, TimeUnit.SECONDS)
            .writeTimeout(25, TimeUnit.SECONDS)
            .addInterceptor(authInterceptor)
            .addInterceptor(logging)
            .build()
    }

    @Synchronized
    fun getApiService(baseUrl: String? = null): ApiService {
        val targetUrl = baseUrl ?: currentBaseUrl.ifBlank { SessionManager.DEFAULT_BASE_URL }
        if (cachedApiService != null && currentBaseUrl == targetUrl) {
            return cachedApiService!!
        }

        currentBaseUrl = targetUrl
        val retrofit = Retrofit.Builder()
            .baseUrl(targetUrl)
            .client(okHttpClient)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()

        val service = retrofit.create(ApiService::class.java)
        cachedApiService = service
        return service
    }

    fun invalidateCache() {
        cachedApiService = null
    }
}
