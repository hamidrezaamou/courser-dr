package com.example.data.api

import com.example.data.model.*
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("login")
    suspend fun login(
        @Body request: LoginRequest
    ): Response<ResponseBody>

    @GET("home")
    suspend fun getHome(): Response<ResponseBody>

    @GET("me")
    suspend fun getMe(): Response<ResponseBody>

    @GET("me/profile")
    suspend fun getMyProfile(): Response<ResponseBody>

    @POST("logout")
    suspend fun logout(): Response<ResponseBody>

    @GET("patients")
    suspend fun getPatients(
        @Query("q") query: String? = null
    ): Response<ResponseBody>

    @POST("patients")
    suspend fun createPatient(
        @Body request: CreatePatientRequest
    ): Response<ResponseBody>

    @GET("patients/{id}")
    suspend fun getPatientDetail(
        @Path("id") patientId: Long
    ): Response<ResponseBody>

    @GET("board")
    suspend fun getBoard(
        @Query("kind") kind: String,
        @Query("date") date: String? = null
    ): Response<ResponseBody>

    @PATCH("appointments/{id}/status")
    suspend fun updateAppointmentStatus(
        @Path("id") id: Long,
        @Body request: ChangeStatusRequest
    ): Response<ResponseBody>

    @PATCH("surgery-appointments/{id}/status")
    suspend fun updateSurgeryAppointmentStatus(
        @Path("id") id: Long,
        @Body request: ChangeStatusRequest
    ): Response<ResponseBody>

    @GET("visit-calendar")
    suspend fun getVisitCalendar(): Response<ResponseBody>

    @GET("slots")
    suspend fun getSlots(
        @Query("date") date: String,
        @Query("kind") kind: String,
        @Query("hospital_id") hospitalId: Long? = null,
        @Query("surgery_type_id") surgeryTypeId: Long? = null,
        @Query("surgery_subtype_id") surgerySubtypeId: Long? = null
    ): Response<ResponseBody>

    @POST("patients/{id}/visits")
    suspend fun bookVisit(
        @Path("id") patientId: Long,
        @Body request: BookVisitRequest
    ): Response<ResponseBody>

    @GET("hospitals")
    suspend fun getHospitals(): Response<ResponseBody>

    @GET("surgery-options")
    suspend fun getSurgeryOptions(
        @Query("hospital_id") hospitalId: Long? = null,
        @Query("surgery_type_id") surgeryTypeId: Long? = null
    ): Response<ResponseBody>

    @POST("patients/{id}/surgeries")
    suspend fun bookSurgery(
        @Path("id") patientId: Long,
        @Body request: BookSurgeryRequest
    ): Response<ResponseBody>

    @POST("patients/{id}/exams")
    suspend fun createExam(
        @Path("id") patientId: Long,
        @Body request: CreateExamRequest
    ): Response<ResponseBody>
}
