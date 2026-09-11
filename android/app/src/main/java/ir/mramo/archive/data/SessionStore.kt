package ir.mramo.archive.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "mramo_session")

class SessionStore(private val context: Context) {
    private val tokenKey = stringPreferencesKey("token")
    private val siteKey = stringPreferencesKey("site")
    private val nameKey = stringPreferencesKey("name")
    private val roleKey = stringPreferencesKey("role")

    val token: Flow<String> = context.dataStore.data.map { it[tokenKey].orEmpty() }
    val site: Flow<String> = context.dataStore.data.map { it[siteKey] ?: DEFAULT_SITE }
    val displayName: Flow<String> = context.dataStore.data.map { it[nameKey].orEmpty() }
    val role: Flow<String> = context.dataStore.data.map { it[roleKey].orEmpty() }

    suspend fun currentToken(): String = token.first()
    suspend fun currentSite(): String = site.first()

    suspend fun saveLogin(token: String, site: String, name: String?, role: String?) {
        context.dataStore.edit {
            it[tokenKey] = token
            it[siteKey] = normalizeSite(site)
            it[nameKey] = name.orEmpty()
            it[roleKey] = role.orEmpty()
        }
    }

    suspend fun saveSite(site: String) {
        context.dataStore.edit { it[siteKey] = normalizeSite(site) }
    }

    suspend fun clearAuth() {
        context.dataStore.edit {
            it.remove(tokenKey)
            it.remove(nameKey)
            it.remove(roleKey)
        }
    }

    companion object {
        const val DEFAULT_SITE = "https://mramo.ir"

        fun normalizeSite(raw: String): String {
            val trimmed = raw.trim().trimEnd('/')
            return if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
                trimmed
            } else {
                "https://$trimmed"
            }
        }

        fun apiRoot(site: String): String = "${normalizeSite(site)}/api/mobile/v1/"
    }
}
