package ir.mramo.archive.data

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface ArchiveApi {
    @POST("login")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @GET("me")
    suspend fun me(): MeResponse

    @POST("logout")
    suspend fun logout(): ApiOk

    @GET("home")
    suspend fun home(): HomeResponse

    @GET("patients")
    suspend fun patients(
        @Query("q") q: String? = null,
        @Query("page") page: Int = 1,
    ): PatientsResponse

    @POST("patients")
    suspend fun createPatient(@Body body: CreatePatientBody): PatientMutationResponse

    @GET("patients/{id}")
    suspend fun patient(@Path("id") id: Long): PatientDetailResponse

    @GET("me/profile")
    suspend fun myProfile(): PatientDetailResponse

    @GET("board")
    suspend fun board(
        @Query("kind") kind: String,
        @Query("date") date: String? = null,
        @Query("hospital_id") hospitalId: Long? = null,
    ): BoardResponse

    @PATCH("appointments/{id}/status")
    suspend fun updateVisitStatus(@Path("id") id: Long, @Body body: StatusBody): ApiOk

    @PATCH("surgery-appointments/{id}/status")
    suspend fun updateSurgeryStatus(@Path("id") id: Long, @Body body: StatusBody): ApiOk

    @GET("hospitals")
    suspend fun hospitals(): HospitalsResponse

    @GET("visit-calendar")
    suspend fun visitCalendar(): VisitCalendarResponse

    @GET("slots")
    suspend fun slots(
        @Query("date") date: String,
        @Query("kind") kind: String,
        @Query("hospital_id") hospitalId: Long? = null,
        @Query("surgery_type_id") surgeryTypeId: Long? = null,
        @Query("surgery_subtype_id") surgerySubtypeId: Long? = null,
    ): SlotsResponse

    @GET("surgery-options")
    suspend fun surgeryOptions(
        @Query("hospital_id") hospitalId: Long? = null,
        @Query("surgery_type_id") surgeryTypeId: Long? = null,
        @Query("surgery_subtype_id") surgerySubtypeId: Long? = null,
        @Query("date") date: String? = null,
    ): SurgeryCatalogResponse

    @POST("patients/{id}/visits")
    suspend fun storeVisit(@Path("id") patientId: Long, @Body body: BookVisitBody): BookingMutationResponse

    @POST("patients/{id}/surgeries")
    suspend fun storeSurgery(@Path("id") patientId: Long, @Body body: BookSurgeryBody): BookingMutationResponse

    @POST("patients/{id}/exams")
    suspend fun storeExam(@Path("id") patientId: Long, @Body body: ExamBody): ApiOk

    @POST("patients/{id}/notes")
    suspend fun storeNote(@Path("id") patientId: Long, @Body body: NoteBody): ApiOk
}
