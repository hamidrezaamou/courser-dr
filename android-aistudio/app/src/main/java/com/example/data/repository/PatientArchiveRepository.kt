package com.example.data.repository

import com.example.data.api.NetworkClient
import com.example.data.local.SessionManager
import com.example.data.model.*
import com.example.data.parseBoard
import com.example.data.parseHospitals
import com.example.data.parseHome
import com.example.data.parseLogin
import com.example.data.parseMe
import com.example.data.parsePatientDetail
import com.example.data.parsePatients
import com.example.data.parseSlots
import com.example.data.parseSurgeryCatalog
import com.example.data.parseVisitCalendar
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.ResponseBody
import org.json.JSONArray
import org.json.JSONObject
import retrofit2.Response
import java.io.IOException
import java.net.SocketTimeoutException
import java.net.UnknownHostException

sealed class ApiResult<out T> {
    data class Success<T>(val data: T) : ApiResult<T>()
    data class Error(val message: String, val code: Int? = null) : ApiResult<Nothing>()
}

class PatientArchiveRepository(
    private val networkClient: NetworkClient,
    private val sessionManager: SessionManager
) {
    private fun parseErrorMessage(errorBody: String?, statusCode: Int): String {
        if (!errorBody.isNullOrBlank()) {
            try {
                val root = JSONObject(errorBody)
                val errors = root.optJSONObject("errors")
                if (errors != null) {
                    val stringErrors = mutableListOf<String>()
                    val keys = errors.keys()
                    while (keys.hasNext()) {
                        val key = keys.next()
                        when (val value = errors.opt(key)) {
                            is JSONArray -> {
                                for (i in 0 until value.length()) {
                                    value.optString(i).takeIf { it.isNotBlank() }?.let { stringErrors.add(it) }
                                }
                            }
                            is String -> if (value.isNotBlank()) stringErrors.add(value)
                        }
                    }
                    if (stringErrors.isNotEmpty()) {
                        return stringErrors.joinToString("\n")
                    }
                }
                val message = root.optString("message").takeIf { it.isNotBlank() && it != "null" }
                    ?: root.optJSONArray("national_code")?.optString(0)
                if (!message.isNullOrBlank()) {
                    return message
                }
            } catch (e: Exception) {
                // fall through
            }
        }

        return when (statusCode) {
            401 -> "نشست کاربری نامعتبر یا منقضی شده است. لطفاً مجدداً وارد شوید."
            403 -> "شما دسترسی لازم برای این عملیات را ندارید."
            404 -> "اطلاعات یا پرونده مورد نظر در سیستم یافت نشد."
            422 -> "اطلاعات وارد شده نامعتبر می‌باشد."
            500 -> "خطای سرور درمانگاه. لطفاً بعداً بررسی نمایید."
            else -> "خطایی در ارتباط با سرور رخ داد (کد $statusCode)."
        }
    }

    private suspend fun <T> safeApiCall(apiCall: suspend () -> Response<ResponseBody>, transform: (String) -> T): ApiResult<T> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiCall()
                val code = response.code()
                val bodyString = response.body()?.string()

                if (response.isSuccessful && bodyString != null) {
                    try {
                        val parsed = transform(bodyString)
                        ApiResult.Success(parsed)
                    } catch (e: Exception) {
                        ApiResult.Error("خطا در پردازش پاسخ سرور: ${e.localizedMessage ?: e.javaClass.simpleName}", code)
                    }
                } else {
                    val errorString = response.errorBody()?.string() ?: bodyString
                    val errorMsg = parseErrorMessage(errorString, code)
                    ApiResult.Error(errorMsg, code)
                }
            } catch (e: SocketTimeoutException) {
                ApiResult.Error("زمان انتظار ارتباط با سرور درمانگاه به پایان رسید. لطفاً مجدداً تلاش کنید.")
            } catch (e: UnknownHostException) {
                ApiResult.Error("خطا در اتصال به اینترنت یا سرور در دسترس نیست.")
            } catch (e: IOException) {
                ApiResult.Error("خطای شبکه: ${e.localizedMessage ?: "عدم اتصال به سرور"}")
            } catch (e: Exception) {
                ApiResult.Error("خطای غیرمنتظره: ${e.localizedMessage ?: "نامشخص"}")
            }
        }
    }

    suspend fun login(nationalCode: String, password: String): ApiResult<LoginResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val api = networkClient.getApiService()
                val response = api.login(LoginRequest(nationalCode.trim(), password.trim()))
                val code = response.code()
                val bodyString = response.body()?.string()

                if (response.isSuccessful && !bodyString.isNullOrBlank()) {
                    val loginRes = parseLogin(bodyString)
                    if (!loginRes.token.isNullOrBlank()) {
                        sessionManager.saveSession(loginRes.token, loginRes.user, loginRes.clinic)
                        networkClient.setCachedToken(loginRes.token)
                        ApiResult.Success(loginRes)
                    } else {
                        ApiResult.Error(loginRes.message ?: "پاسخ ورود نامعتبر است.", code)
                    }
                } else {
                    val errorString = response.errorBody()?.string() ?: bodyString
                    val errorMsg = parseErrorMessage(errorString, code)
                    ApiResult.Error(errorMsg, code)
                }
            } catch (e: SocketTimeoutException) {
                ApiResult.Error("زمان برقراری ارتباط با سرور پایان یافت (بیش از ۲۵ ثانیه).")
            } catch (e: Exception) {
                ApiResult.Error("خطا در ورود به سامانه: ${e.localizedMessage ?: "نامشخص"}")
            }
        }
    }

    suspend fun getHome(): ApiResult<HomeResponse> {
        return safeApiCall({ networkClient.getApiService().getHome() }) { json ->
            parseHome(json)
        }
    }

    suspend fun getMe(): ApiResult<User?> {
        return safeApiCall({ networkClient.getApiService().getMe() }) { json ->
            parseMe(json).first
        }
    }

    suspend fun getMyProfile(): ApiResult<PatientProfileResponse> {
        return safeApiCall({ networkClient.getApiService().getMyProfile() }) { json ->
            val detail = parsePatientDetail(json)
            PatientProfileResponse(
                patient = detail.patient,
                timeline = detail.timeline,
            )
        }
    }

    suspend fun logout(): ApiResult<Boolean> {
        return withContext(Dispatchers.IO) {
            try {
                networkClient.getApiService().logout()
            } catch (ignored: Exception) {
            } finally {
                sessionManager.clearSession()
            }
            ApiResult.Success(true)
        }
    }

    suspend fun getPatients(query: String?): ApiResult<List<Patient>> {
        return safeApiCall({ networkClient.getApiService().getPatients(query) }) { json ->
            parsePatients(json)
        }
    }

    suspend fun createPatient(name: String, nationalCode: String, mobile: String, age: String): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().createPatient(CreatePatientRequest(name, nationalCode, mobile, age))
        }) { "بیمار با موفقیت در سامانه ثبت شد." }
    }

    suspend fun getPatientDetail(patientId: Long): ApiResult<PatientDetailResponse> {
        return safeApiCall({ networkClient.getApiService().getPatientDetail(patientId) }) { json ->
            parsePatientDetail(json)
        }
    }

    suspend fun getBoard(kind: String, date: String?): ApiResult<BoardResponse> {
        return safeApiCall({ networkClient.getApiService().getBoard(kind, date) }) { json ->
            parseBoard(json)
        }
    }

    suspend fun updateAppointmentStatus(id: Long, status: String): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().updateAppointmentStatus(id, ChangeStatusRequest(status))
        }) { "وضعیت نوبت ویزیت با موفقیت به‌روزرسانی شد." }
    }

    suspend fun updateSurgeryAppointmentStatus(id: Long, status: String): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().updateSurgeryAppointmentStatus(id, ChangeStatusRequest(status))
        }) { "وضعیت نوبت جراحی با موفقیت به‌روزرسانی شد." }
    }

    suspend fun getVisitCalendar(): ApiResult<VisitCalendarResponse> {
        return safeApiCall({ networkClient.getApiService().getVisitCalendar() }) { json ->
            parseVisitCalendar(json)
        }
    }

    suspend fun getSlots(
        date: String,
        kind: String,
        hospitalId: Long? = null,
        surgeryTypeId: Long? = null,
        surgerySubtypeId: Long? = null
    ): ApiResult<List<Slot>> {
        return safeApiCall({
            networkClient.getApiService().getSlots(date, kind, hospitalId, surgeryTypeId, surgerySubtypeId)
        }) { json ->
            parseSlots(json)
        }
    }

    suspend fun bookVisit(patientId: Long, request: BookVisitRequest): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().bookVisit(patientId, request)
        }) { "نوبت ویزیت با موفقیت رزرو شد." }
    }

    suspend fun getHospitals(): ApiResult<List<Hospital>> {
        return safeApiCall({ networkClient.getApiService().getHospitals() }) { json ->
            parseHospitals(json)
        }
    }

    suspend fun getSurgeryOptions(hospitalId: Long? = null, surgeryTypeId: Long? = null): ApiResult<SurgeryCatalog> {
        return safeApiCall({ networkClient.getApiService().getSurgeryOptions(hospitalId, surgeryTypeId) }) { json ->
            val (types, days) = parseSurgeryCatalog(json)
            SurgeryCatalog(types = types, days = days)
        }
    }

    suspend fun bookSurgery(patientId: Long, request: BookSurgeryRequest): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().bookSurgery(patientId, request)
        }) { "نوبت جراحی با موفقیت ثبت شد." }
    }

    suspend fun createExam(patientId: Long, request: CreateExamRequest): ApiResult<String> {
        return safeApiCall({
            networkClient.getApiService().createExam(patientId, request)
        }) { "اطلاعات معاینه و تجویز بیمار با موفقیت ذخیره گردید." }
    }
}
