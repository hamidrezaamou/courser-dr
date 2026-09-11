package com.example.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.example.data.model.Clinic
import com.example.data.model.User
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "patient_archive_session")

class SessionManager(private val context: Context) {

    private val moshi = Moshi.Builder()
        .add(KotlinJsonAdapterFactory())
        .build()
    private val userAdapter = moshi.adapter(User::class.java)
    private val clinicAdapter = moshi.adapter(Clinic::class.java)

    companion object {
        val KEY_TOKEN = stringPreferencesKey("auth_token")
        val KEY_USER = stringPreferencesKey("auth_user")
        val KEY_CLINIC = stringPreferencesKey("auth_clinic")
        val KEY_BASE_URL = stringPreferencesKey("base_url")
        const val DEFAULT_BASE_URL = "https://mramo.ir/api/mobile/v1/"
    }

    val tokenFlow: Flow<String?> = context.dataStore.data.map { preferences ->
        preferences[KEY_TOKEN]
    }

    val baseUrlFlow: Flow<String> = context.dataStore.data.map { preferences ->
        preferences[KEY_BASE_URL]?.takeIf { it.isNotBlank() } ?: DEFAULT_BASE_URL
    }

    val userFlow: Flow<User?> = context.dataStore.data.map { preferences ->
        preferences[KEY_USER]?.let {
            try {
                userAdapter.fromJson(it)
            } catch (e: Exception) {
                null
            }
        }
    }

    val clinicFlow: Flow<Clinic?> = context.dataStore.data.map { preferences ->
        preferences[KEY_CLINIC]?.let {
            try {
                clinicAdapter.fromJson(it)
            } catch (e: Exception) {
                null
            }
        }
    }

    suspend fun saveSession(token: String, user: User?, clinic: Clinic?) {
        context.dataStore.edit { preferences ->
            preferences[KEY_TOKEN] = token
            user?.let {
                preferences[KEY_USER] = userAdapter.toJson(it)
            }
            clinic?.let {
                preferences[KEY_CLINIC] = clinicAdapter.toJson(it)
            }
        }
    }

    suspend fun updateBaseUrl(newUrl: String) {
        var cleanUrl = newUrl.trim()
        if (!cleanUrl.startsWith("http://") && !cleanUrl.startsWith("https://")) {
            cleanUrl = "https://$cleanUrl"
        }
        if (!cleanUrl.endsWith("/")) {
            cleanUrl = "$cleanUrl/"
        }
        // If user entered only domain e.g. https://mramo.ir/, append api/mobile/v1/
        if (!cleanUrl.contains("/api/")) {
            cleanUrl = "${cleanUrl}api/mobile/v1/"
        }
        context.dataStore.edit { preferences ->
            preferences[KEY_BASE_URL] = cleanUrl
        }
    }

    suspend fun clearSession() {
        context.dataStore.edit { preferences ->
            preferences.remove(KEY_TOKEN)
            preferences.remove(KEY_USER)
            preferences.remove(KEY_CLINIC)
        }
    }
}
