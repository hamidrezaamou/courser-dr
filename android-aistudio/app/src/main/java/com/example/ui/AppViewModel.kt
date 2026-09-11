package com.example.ui

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.example.data.api.NetworkClient
import com.example.data.local.SessionManager
import com.example.data.model.*
import com.example.data.repository.ApiResult
import com.example.data.repository.PatientArchiveRepository
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch

data class AuthUiState(
    val isLoading: Boolean = false,
    val token: String? = null,
    val user: User? = null,
    val clinic: Clinic? = null,
    val baseUrl: String = SessionManager.DEFAULT_BASE_URL,
    val errorMessage: String? = null
)

data class HomeUiState(
    val isLoading: Boolean = false,
    val homeData: HomeResponse? = null,
    val errorMessage: String? = null
)

data class PatientsUiState(
    val isLoading: Boolean = false,
    val query: String = "",
    val patients: List<Patient> = emptyList(),
    val errorMessage: String? = null,
    val isCreating: Boolean = false,
    val createSuccessMessage: String? = null
)

data class PatientDetailUiState(
    val isLoading: Boolean = false,
    val patient: Patient? = null,
    val timeline: List<TimelineItem> = emptyList(),
    val errorMessage: String? = null,
    val actionSuccessMessage: String? = null
)

data class BoardUiState(
    val isLoading: Boolean = false,
    val kind: String = "visit", // "visit" or "surgery"
    val date: String? = null,
    val boardData: BoardResponse? = null,
    val errorMessage: String? = null,
    val actionInProgress: Boolean = false
)

data class BookingState(
    val isLoadingCalendar: Boolean = false,
    val calendarDays: Map<String, CalendarDayInfo> = emptyMap(),
    val selectedDate: String? = null,
    val isLoadingSlots: Boolean = false,
    val slots: List<Slot> = emptyList(),
    val selectedSlot: String? = null,
    val hospitals: List<Hospital> = emptyList(),
    val selectedHospital: Hospital? = null,
    val surgeryTypes: List<SurgeryOption> = emptyList(),
    val selectedSurgeryType: SurgeryOption? = null,
    val surgerySubtypes: List<SurgeryOption> = emptyList(),
    val selectedSurgerySubtype: SurgeryOption? = null,
    val eyeSide: String = "OD",
    val isSubmitting: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null
)

data class PatientProfileUiState(
    val isLoading: Boolean = false,
    val profile: PatientProfileResponse? = null,
    val errorMessage: String? = null
)

class AppViewModel(application: Application) : AndroidViewModel(application) {

    val sessionManager = SessionManager(application)
    val networkClient = NetworkClient(sessionManager)
    val repository = PatientArchiveRepository(networkClient, sessionManager)

    private val _authUiState = MutableStateFlow(AuthUiState())
    val authUiState: StateFlow<AuthUiState> = _authUiState.asStateFlow()

    private val _homeUiState = MutableStateFlow(HomeUiState())
    val homeUiState: StateFlow<HomeUiState> = _homeUiState.asStateFlow()

    private val _patientsUiState = MutableStateFlow(PatientsUiState())
    val patientsUiState: StateFlow<PatientsUiState> = _patientsUiState.asStateFlow()

    private val _patientDetailUiState = MutableStateFlow(PatientDetailUiState())
    val patientDetailUiState: StateFlow<PatientDetailUiState> = _patientDetailUiState.asStateFlow()

    private val _boardUiState = MutableStateFlow(BoardUiState())
    val boardUiState: StateFlow<BoardUiState> = _boardUiState.asStateFlow()

    private val _bookingState = MutableStateFlow(BookingState())
    val bookingState: StateFlow<BookingState> = _bookingState.asStateFlow()

    private val _patientProfileUiState = MutableStateFlow(PatientProfileUiState())
    val patientProfileUiState: StateFlow<PatientProfileUiState> = _patientProfileUiState.asStateFlow()

    private val _userFeedbackMessage = MutableSharedFlow<String>()
    val userFeedbackMessage: SharedFlow<String> = _userFeedbackMessage.asSharedFlow()

    init {
        viewModelScope.launch {
            combine(
                sessionManager.tokenFlow,
                sessionManager.userFlow,
                sessionManager.clinicFlow,
                sessionManager.baseUrlFlow
            ) { token, user, clinic, baseUrl ->
                AuthUiState(
                    token = token,
                    user = user,
                    clinic = clinic,
                    baseUrl = baseUrl
                )
            }.collect { newState ->
                val prevToken = _authUiState.value.token
                networkClient.setCachedToken(newState.token)
                _authUiState.value = newState

                if (prevToken == null && newState.token != null) {
                    // Just logged in
                    onLoggedIn(newState.user)
                }
            }
        }
    }

    private fun onLoggedIn(user: User?) {
        if (user?.isStaff == true) {
            loadHome()
            loadPatients()
            loadBoard()
        } else {
            loadPatientSelfProfile()
        }
    }

    fun updateBaseUrl(newUrl: String) {
        viewModelScope.launch {
            sessionManager.updateBaseUrl(newUrl)
            sessionManager.baseUrlFlow.first().let { networkClient.setBaseUrl(it) }
            networkClient.invalidateCache()
            _userFeedbackMessage.emit("آدرس سرور با موفقیت به‌روزرسانی شد.")
        }
    }

    fun login(nationalCode: String, pass: String) {
        if (nationalCode.isBlank() || pass.isBlank()) {
            _authUiState.update { it.copy(errorMessage = "لطفاً کد ملی و رمز عبور را وارد کنید.") }
            return
        }

        viewModelScope.launch {
            _authUiState.update { it.copy(isLoading = true, errorMessage = null) }
            val res = repository.login(nationalCode, pass)
            when (res) {
                is ApiResult.Success -> {
                    _authUiState.update {
                        it.copy(
                            isLoading = false,
                            token = res.data.token,
                            user = res.data.user,
                            clinic = res.data.clinic,
                            errorMessage = null
                        )
                    }
                    onLoggedIn(res.data.user)
                }
                is ApiResult.Error -> {
                    _authUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }

    fun clearLoginError() {
        _authUiState.update { it.copy(errorMessage = null) }
    }

    fun logout() {
        viewModelScope.launch {
            repository.logout()
            networkClient.setCachedToken(null)
            _homeUiState.value = HomeUiState()
            _patientsUiState.value = PatientsUiState()
            _patientDetailUiState.value = PatientDetailUiState()
            _boardUiState.value = BoardUiState()
            _patientProfileUiState.value = PatientProfileUiState()
            _userFeedbackMessage.emit("با موفقیت از سامانه خارج شدید.")
        }
    }

    // HOME
    fun loadHome() {
        viewModelScope.launch {
            _homeUiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val res = repository.getHome()) {
                is ApiResult.Success -> {
                    _homeUiState.update { it.copy(isLoading = false, homeData = res.data) }
                }
                is ApiResult.Error -> {
                    _homeUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }

    // PATIENTS
    fun searchPatients(query: String) {
        _patientsUiState.update { it.copy(query = query) }
        loadPatients(query)
    }

    fun loadPatients(query: String? = _patientsUiState.value.query) {
        viewModelScope.launch {
            _patientsUiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val res = repository.getPatients(query?.takeIf { it.isNotBlank() })) {
                is ApiResult.Success -> {
                    _patientsUiState.update { it.copy(isLoading = false, patients = res.data) }
                }
                is ApiResult.Error -> {
                    _patientsUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }

    fun createPatient(name: String, nationalCode: String, mobile: String, age: String, onSuccess: () -> Unit) {
        if (name.isBlank() || nationalCode.isBlank()) {
            _patientsUiState.update { it.copy(errorMessage = "نام و کد ملی الزامی است.") }
            return
        }

        viewModelScope.launch {
            _patientsUiState.update { it.copy(isCreating = true, errorMessage = null) }
            when (val res = repository.createPatient(name, nationalCode, mobile, age)) {
                is ApiResult.Success -> {
                    _patientsUiState.update {
                        it.copy(isCreating = false, createSuccessMessage = res.data)
                    }
                    _userFeedbackMessage.emit(res.data)
                    loadPatients()
                    onSuccess()
                }
                is ApiResult.Error -> {
                    _patientsUiState.update { it.copy(isCreating = false, errorMessage = res.message) }
                }
            }
        }
    }

    // PATIENT DETAIL
    fun loadPatientDetail(patientId: Long) {
        viewModelScope.launch {
            _patientDetailUiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val res = repository.getPatientDetail(patientId)) {
                is ApiResult.Success -> {
                    _patientDetailUiState.update {
                        it.copy(
                            isLoading = false,
                            patient = res.data.patient,
                            timeline = res.data.timeline ?: emptyList()
                        )
                    }
                }
                is ApiResult.Error -> {
                    _patientDetailUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }

    // BOARD
    fun setBoardKind(kind: String) {
        _boardUiState.update { it.copy(kind = kind) }
        loadBoard(kind = kind, date = _boardUiState.value.date)
    }

    fun setBoardDate(date: String?) {
        _boardUiState.update { it.copy(date = date) }
        loadBoard(kind = _boardUiState.value.kind, date = date)
    }

    fun loadBoard(kind: String = _boardUiState.value.kind, date: String? = _boardUiState.value.date) {
        viewModelScope.launch {
            _boardUiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val res = repository.getBoard(kind, date)) {
                is ApiResult.Success -> {
                    _boardUiState.update { it.copy(isLoading = false, boardData = res.data) }
                }
                is ApiResult.Error -> {
                    _boardUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }

    fun changeAppointmentStatus(itemId: Long, status: String, kind: String) {
        viewModelScope.launch {
            _boardUiState.update { it.copy(actionInProgress = true) }
            val res = if (kind.contains("surg", ignoreCase = true) || _boardUiState.value.kind == "surgery") {
                repository.updateSurgeryAppointmentStatus(itemId, status)
            } else {
                repository.updateAppointmentStatus(itemId, status)
            }
            when (res) {
                is ApiResult.Success -> {
                    _boardUiState.update { it.copy(actionInProgress = false) }
                    _userFeedbackMessage.emit(res.data)
                    loadBoard()
                }
                is ApiResult.Error -> {
                    _boardUiState.update { it.copy(actionInProgress = false) }
                    _userFeedbackMessage.emit(res.message)
                }
            }
        }
    }

    // BOOK VISIT
    fun initVisitBooking() {
        _bookingState.update {
            BookingState(
                isLoadingCalendar = true,
                selectedDate = null,
                selectedSlot = null
            )
        }
        viewModelScope.launch {
            when (val res = repository.getVisitCalendar()) {
                is ApiResult.Success -> {
                    val days = res.data.days ?: emptyMap()
                    val firstAvailableDay = days.keys.sorted().firstOrNull()
                    _bookingState.update {
                        it.copy(
                            isLoadingCalendar = false,
                            calendarDays = days,
                            selectedDate = firstAvailableDay
                        )
                    }
                    if (firstAvailableDay != null) {
                        loadVisitSlots(firstAvailableDay)
                    }
                }
                is ApiResult.Error -> {
                    _bookingState.update {
                        it.copy(isLoadingCalendar = false, errorMessage = res.message)
                    }
                }
            }
        }
    }

    fun selectBookingDate(date: String, kind: String = "visit") {
        _bookingState.update { it.copy(selectedDate = date, selectedSlot = null) }
        if (kind == "visit") {
            loadVisitSlots(date)
        } else {
            loadSurgerySlots(date)
        }
    }

    private fun loadVisitSlots(date: String) {
        viewModelScope.launch {
            _bookingState.update { it.copy(isLoadingSlots = true, slots = emptyList()) }
            when (val res = repository.getSlots(date = date, kind = "visit")) {
                is ApiResult.Success -> {
                    _bookingState.update { it.copy(isLoadingSlots = false, slots = res.data) }
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(isLoadingSlots = false, errorMessage = res.message) }
                }
            }
        }
    }

    fun selectSlot(slotValue: String) {
        _bookingState.update { it.copy(selectedSlot = slotValue) }
    }

    fun submitBookVisit(patient: Patient, onSuccess: () -> Unit) {
        val state = _bookingState.value
        val date = state.selectedDate
        val slot = state.selectedSlot

        if (date.isNullOrBlank() || slot.isNullOrBlank()) {
            _bookingState.update { it.copy(errorMessage = "لطفاً تاریخ و ساعت نوبت را انتخاب کنید.") }
            return
        }

        viewModelScope.launch {
            _bookingState.update { it.copy(isSubmitting = true, errorMessage = null) }
            val req = BookVisitRequest(
                patientName = patient.name ?: "",
                nationalCode = patient.nationalCode ?: "",
                mobile = patient.mobile ?: "",
                age = patient.age?.toString() ?: "",
                visitType = "ویزیت",
                scheduledDate = date,
                scheduledTime = slot
            )
            when (val res = repository.bookVisit(patient.id ?: 0, req)) {
                is ApiResult.Success -> {
                    _bookingState.update { it.copy(isSubmitting = false, successMessage = res.data) }
                    _userFeedbackMessage.emit(res.data)
                    patient.id?.let { loadPatientDetail(it) }
                    onSuccess()
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(isSubmitting = false, errorMessage = res.message) }
                }
            }
        }
    }

    // BOOK SURGERY
    fun initSurgeryBooking() {
        _bookingState.update {
            BookingState(eyeSide = "OD")
        }
        viewModelScope.launch {
            when (val res = repository.getHospitals()) {
                is ApiResult.Success -> {
                    val hospitals = res.data
                    val first = hospitals.firstOrNull()
                    _bookingState.update { it.copy(hospitals = hospitals, selectedHospital = first) }
                    if (first?.id != null) {
                        loadSurgeryTypes(first.id)
                    }
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(errorMessage = res.message) }
                }
            }
        }
    }

    fun selectHospital(hospital: Hospital) {
        _bookingState.update {
            it.copy(
                selectedHospital = hospital,
                selectedSurgeryType = null,
                selectedSurgerySubtype = null,
                surgerySubtypes = emptyList(),
                calendarDays = emptyMap(),
                selectedDate = null,
                slots = emptyList(),
                selectedSlot = null
            )
        }
        hospital.id?.let { loadSurgeryTypes(it) }
    }

    private fun loadSurgeryTypes(hospitalId: Long) {
        viewModelScope.launch {
            when (val res = repository.getSurgeryOptions(hospitalId = hospitalId)) {
                is ApiResult.Success -> {
                    val types = res.data.types
                    val firstType = types.firstOrNull()
                    val defaultSubtype = defaultSubtypeFor(firstType)
                    _bookingState.update {
                        it.copy(
                            surgeryTypes = types,
                            selectedSurgeryType = firstType,
                            surgerySubtypes = firstType?.subtypes.orEmpty(),
                            selectedSurgerySubtype = defaultSubtype
                        )
                    }
                    firstType?.id?.let { loadSurgeryCalendar(hospitalId, it) }
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(errorMessage = res.message) }
                }
            }
        }
    }

    fun selectSurgeryType(type: SurgeryOption) {
        _bookingState.update {
            it.copy(
                selectedSurgeryType = type,
                selectedSurgerySubtype = defaultSubtypeFor(type),
                surgerySubtypes = type.subtypes,
                slots = emptyList(),
                selectedSlot = null,
                selectedDate = null
            )
        }
        val hospId = _bookingState.value.selectedHospital?.id
        if (hospId != null && type.id != null) {
            loadSurgeryCalendar(hospId, type.id)
        }
    }

    private fun loadSurgeryCalendar(hospitalId: Long, surgeryTypeId: Long) {
        viewModelScope.launch {
            _bookingState.update { it.copy(isLoadingCalendar = true) }
            when (val res = repository.getSurgeryOptions(hospitalId = hospitalId, surgeryTypeId = surgeryTypeId)) {
                is ApiResult.Success -> {
                    val days = res.data.days.filter { it.value.status != "full" }
                    val firstDay = days.keys.sorted().firstOrNull()
                    _bookingState.update {
                        it.copy(
                            isLoadingCalendar = false,
                            calendarDays = days,
                            selectedDate = firstDay,
                            slots = emptyList()
                        )
                    }
                    if (firstDay != null) {
                        loadSurgerySlots(firstDay)
                    }
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(isLoadingCalendar = false, errorMessage = res.message) }
                }
            }
        }
    }

    private fun defaultSubtypeFor(type: SurgeryOption?): SurgeryOption? {
        if (type == null) return null
        if (type.hasGeneral) return null
        return type.subtypes.firstOrNull()
    }

    fun selectSurgerySubtype(subtype: SurgeryOption?) {
        _bookingState.update { it.copy(selectedSurgerySubtype = subtype, selectedSlot = null) }
        loadSurgerySlots(_bookingState.value.selectedDate)
    }

    fun setEyeSide(side: String) {
        _bookingState.update { it.copy(eyeSide = side) }
    }

    fun loadSurgerySlots(date: String? = _bookingState.value.selectedDate) {
        val state = _bookingState.value
        val chosenDate = date ?: return
        val hospitalId = state.selectedHospital?.id ?: return
        val typeId = state.selectedSurgeryType?.id ?: return
        val subtypeId = state.selectedSurgerySubtype?.id

        viewModelScope.launch {
            _bookingState.update { it.copy(isLoadingSlots = true, selectedDate = chosenDate) }
            val res = repository.getSlots(
                date = chosenDate,
                kind = "surgery",
                hospitalId = hospitalId,
                surgeryTypeId = typeId,
                surgerySubtypeId = subtypeId
            )
            when (res) {
                is ApiResult.Success -> {
                    _bookingState.update { it.copy(isLoadingSlots = false, slots = res.data) }
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(isLoadingSlots = false, errorMessage = res.message) }
                }
            }
        }
    }

    fun submitBookSurgery(patient: Patient, onSuccess: () -> Unit) {
        val state = _bookingState.value
        val hosp = state.selectedHospital
        val sType = state.selectedSurgeryType
        val sSubtype = state.selectedSurgerySubtype
        val date = state.selectedDate
        val slot = state.selectedSlot

        if (hosp?.id == null) {
            _bookingState.update { it.copy(errorMessage = "لطفاً بیمارستان را انتخاب فرمایید.") }
            return
        }
        if (sType == null) {
            _bookingState.update { it.copy(errorMessage = "لطفاً نوع عمل را انتخاب کنید.") }
            return
        }
        if (date.isNullOrBlank()) {
            _bookingState.update { it.copy(errorMessage = "لطفاً روز عمل را از تقویم تایم‌ها انتخاب کنید.") }
            return
        }
        if (slot.isNullOrBlank()) {
            _bookingState.update { it.copy(errorMessage = "لطفاً ساعت نوبت جراحی را مشخص فرمایید.") }
            return
        }

        viewModelScope.launch {
            _bookingState.update { it.copy(isSubmitting = true, errorMessage = null) }
            val req = BookSurgeryRequest(
                patientName = patient.name ?: "",
                nationalCode = patient.nationalCode ?: "",
                mobile = patient.mobile ?: "",
                age = patient.age,
                hospitalId = hosp.id,
                surgeryType = sType.name ?: sType.title ?: "عمل",
                surgeryTypeId = sType.id,
                surgerySubtypeId = sSubtype?.id,
                eyeSide = state.eyeSide,
                scheduledDate = date,
                scheduledTime = slot
            )
            when (val res = repository.bookSurgery(patient.id ?: 0, req)) {
                is ApiResult.Success -> {
                    _bookingState.update { it.copy(isSubmitting = false, successMessage = res.data) }
                    _userFeedbackMessage.emit(res.data)
                    patient.id?.let { loadPatientDetail(it) }
                    onSuccess()
                }
                is ApiResult.Error -> {
                    _bookingState.update { it.copy(isSubmitting = false, errorMessage = res.message) }
                }
            }
        }
    }

    // EXAM
    fun submitExam(patientId: Long, request: CreateExamRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            when (val res = repository.createExam(patientId, request)) {
                is ApiResult.Success -> {
                    _userFeedbackMessage.emit(res.data)
                    loadPatientDetail(patientId)
                    onSuccess()
                }
                is ApiResult.Error -> {
                    _userFeedbackMessage.emit(res.message)
                }
            }
        }
    }

    // PATIENT SELF PROFILE
    fun loadPatientSelfProfile() {
        viewModelScope.launch {
            _patientProfileUiState.update { it.copy(isLoading = true, errorMessage = null) }
            when (val res = repository.getMyProfile()) {
                is ApiResult.Success -> {
                    _patientProfileUiState.update { it.copy(isLoading = false, profile = res.data) }
                }
                is ApiResult.Error -> {
                    _patientProfileUiState.update { it.copy(isLoading = false, errorMessage = res.message) }
                }
            }
        }
    }
}
