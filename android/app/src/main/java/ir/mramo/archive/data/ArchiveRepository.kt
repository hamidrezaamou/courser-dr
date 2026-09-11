package ir.mramo.archive.data

import com.google.gson.Gson
import com.google.gson.GsonBuilder
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.HttpException
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicReference

class ApiException(message: String) : RuntimeException(message)

class ArchiveRepository(private val session: SessionStore) {
    private val gson: Gson = GsonBuilder().serializeNulls().create()
    private val tokenRef = AtomicReference("")
    @Volatile private var cachedRoot: String? = null
    @Volatile private var cachedApi: ArchiveApi? = null

    suspend fun hydrate() {
        tokenRef.set(session.currentToken())
    }

    private fun client(): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        }
        val auth = Interceptor { chain ->
            val token = tokenRef.get()
            val builder = chain.request().newBuilder()
                .header("Accept", "application/json")
                .header("X-Requested-With", "XMLHttpRequest")
            if (token.isNotBlank()) {
                builder.header("Authorization", "Bearer $token")
            }
            chain.proceed(builder.build())
        }
        return OkHttpClient.Builder()
            .addInterceptor(auth)
            .addInterceptor(logging)
            .connectTimeout(25, TimeUnit.SECONDS)
            .readTimeout(40, TimeUnit.SECONDS)
            .build()
    }

    private suspend fun api(): ArchiveApi {
        val root = SessionStore.apiRoot(session.currentSite())
        cachedApi?.let { current ->
            if (cachedRoot == root) return current
        }
        val created = Retrofit.Builder()
            .baseUrl(root)
            .client(client())
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ArchiveApi::class.java)
        cachedRoot = root
        cachedApi = created
        return created
    }

    private fun parseError(raw: String?): String {
        if (raw.isNullOrBlank()) return "ارتباط با سرور برقرار نشد."
        return try {
            val parsed = gson.fromJson(raw, LoginResponse::class.java)
            parsed.message
                ?: parsed.user?.name
                ?: extractFirstError(raw)
                ?: "خطای سرور"
        } catch (_: Exception) {
            extractFirstError(raw) ?: raw.take(180)
        }
    }

    private fun extractFirstError(raw: String): String? {
        return try {
            val tree = com.google.gson.JsonParser.parseString(raw).asJsonObject
            if (tree.has("message") && tree.get("message").isJsonPrimitive) {
                val msg = tree.get("message").asString
                if (msg.isNotBlank() && msg != "The given data was invalid.") return msg
            }
            val errors = tree.getAsJsonObject("errors") ?: return tree.get("message")?.asString
            errors.entrySet().firstOrNull()?.value?.asJsonArray?.firstOrNull()?.asString
        } catch (_: Exception) {
            null
        }
    }

    private suspend fun <T> call(block: suspend ArchiveApi.() -> T): T = withContext(Dispatchers.IO) {
        try {
            api().block()
        } catch (http: HttpException) {
            if (http.code() == 401) {
                session.clearAuth()
                tokenRef.set("")
            }
            val body = http.response()?.errorBody()?.string()
            throw ApiException(parseError(body) ?: "خطای ${http.code()}")
        } catch (api: ApiException) {
            throw api
        } catch (_: Exception) {
            throw ApiException("اینترنت یا آدرس سایت را بررسی کنید.")
        }
    }

    suspend fun login(nationalCode: String, password: String, site: String): UserDto {
        session.saveSite(site)
        cachedApi = null
        val response = call {
            login(
                LoginRequest(
                    nationalCode = nationalCode.filter { it.isDigit() },
                    password = password,
                    deviceName = android.os.Build.MODEL ?: "android",
                ),
            )
        }
        val token = response.token ?: throw ApiException(response.message ?: "ورود ناموفق بود.")
        val user = response.user ?: throw ApiException("پاسخ ورود ناقص است.")
        session.saveLogin(token, site, user.name, user.role)
        tokenRef.set(token)
        cachedApi = null
        return user
    }

    suspend fun logout() {
        runCatching { call { logout() } }
        session.clearAuth()
        tokenRef.set("")
        cachedApi = null
    }

    suspend fun home() = call { home() }
    suspend fun patients(q: String, page: Int = 1) = call { patients(q.ifBlank { null }, page) }
    suspend fun createPatient(body: CreatePatientBody) = call { createPatient(body) }
    suspend fun patient(id: Long) = call { patient(id) }
    suspend fun board(kind: String, date: String?) = call { board(kind, date) }
    suspend fun updateStatus(kind: String, id: Long, status: String) = call {
        if (kind == "surgery") updateSurgeryStatus(id, StatusBody(status))
        else updateVisitStatus(id, StatusBody(status))
    }
    suspend fun visitCalendar() = call { visitCalendar() }
    suspend fun slots(date: String, kind: String, hospitalId: Long? = null, typeId: Long? = null, subtypeId: Long? = null) =
        call { slots(date, kind, hospitalId, typeId, subtypeId) }
    suspend fun hospitals() = call { hospitals() }
    suspend fun surgeryCatalog(hospitalId: Long) = call { surgeryOptions(hospitalId = hospitalId) }
    suspend fun surgeryCalendar(hospitalId: Long, typeId: Long, subtypeId: Long?) =
        call { surgeryOptions(hospitalId, typeId, subtypeId) }
    suspend fun storeVisit(patientId: Long, body: BookVisitBody) = call { storeVisit(patientId, body) }
    suspend fun storeSurgery(patientId: Long, body: BookSurgeryBody) = call { storeSurgery(patientId, body) }
    suspend fun storeExam(patientId: Long, body: ExamBody) = call { storeExam(patientId, body) }
    suspend fun storeNote(patientId: Long, body: NoteBody) = call { storeNote(patientId, body) }
}
