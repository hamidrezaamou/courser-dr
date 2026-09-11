<?php

use App\Http\Controllers\Api\HisSyncController;
use App\Http\Controllers\Api\PublicVisitBookingController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BoardApiController;
use App\Http\Controllers\Api\Mobile\BookingApiController;
use App\Http\Controllers\Api\Mobile\ClinicalApiController;
use App\Http\Controllers\Api\Mobile\HomeController;
use App\Http\Controllers\Api\Mobile\PatientApiController;
use App\Http\Controllers\Api\Mobile\PanelApiController;
use App\Http\Controllers\Api\Mobile\ClinicOpsApiController;
use App\Http\Controllers\Api\Mobile\ClinicToolsApiController;
use App\Http\Middleware\AuthenticateMobile;
use App\Http\Middleware\EnsureMobileStaff;
use App\Http\Middleware\VerifyHisAgentKey;
use App\Http\Middleware\VerifyWebsiteApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyWebsiteApiKey::class)->prefix('public')->group(function () {
    Route::get('/visit/days', [PublicVisitBookingController::class, 'days']);
    Route::get('/visit/slots', [PublicVisitBookingController::class, 'slots']);
    Route::post('/visit/book', [PublicVisitBookingController::class, 'book']);
});

// Push target for the agent on the clinic's HIS server. One way in only.
Route::middleware(VerifyHisAgentKey::class)->prefix('his')->group(function () {
    Route::get('/ping', [HisSyncController::class, 'ping']);
    Route::get('/cursor/{resource}', [HisSyncController::class, 'cursor']);
    Route::post('/sync/{resource}', [HisSyncController::class, 'store']);
});

Route::prefix('mobile/v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(AuthenticateMobile::class)->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'updateMe']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/home', [HomeController::class, 'show']);
        Route::get('/me/profile', [PatientApiController::class, 'myProfile']);

        Route::middleware(EnsureMobileStaff::class)->group(function () {
            Route::get('/patients', [PatientApiController::class, 'index']);
            Route::post('/patients', [PatientApiController::class, 'store']);
            Route::get('/patients/lookup', [BookingApiController::class, 'lookup']);
            Route::get('/patients/{patient}', [PatientApiController::class, 'show']);
            Route::patch('/patients/{patient}', [PatientApiController::class, 'update']);

            Route::get('/board', [BoardApiController::class, 'show']);
            Route::get('/appointments/{appointment}', [BookingApiController::class, 'showVisit']);
            Route::put('/appointments/{appointment}', [BookingApiController::class, 'updateVisit']);
            Route::patch('/appointments/{appointment}/status', [BoardApiController::class, 'updateVisitStatus']);
            Route::get('/surgery-appointments/{surgeryAppointment}', [BookingApiController::class, 'showSurgery']);
            Route::put('/surgery-appointments/{surgeryAppointment}', [BookingApiController::class, 'updateSurgery']);
            Route::patch('/surgery-appointments/{surgeryAppointment}/status', [BoardApiController::class, 'updateSurgeryStatus']);
            Route::get('/surgeries/cooldown-check', [BookingApiController::class, 'cooldownCheck']);

            Route::get('/hospitals', [BookingApiController::class, 'hospitals']);
            Route::get('/visit-calendar', [BookingApiController::class, 'visitCalendar']);
            Route::get('/slots', [BookingApiController::class, 'slots']);
            Route::get('/surgery-options', [BookingApiController::class, 'surgeryOptions']);
            Route::post('/visits', [BookingApiController::class, 'registerVisit']);
            Route::post('/patients/{patient}/visits', [BookingApiController::class, 'storeVisit']);
            Route::post('/surgeries', [BookingApiController::class, 'registerSurgery']);
            Route::post('/patients/{patient}/surgeries', [BookingApiController::class, 'storeSurgery']);

            Route::post('/patients/{patient}/exams', [ClinicalApiController::class, 'storeExam']);
            Route::patch('/patients/{patient}/exams/{visit}', [ClinicalApiController::class, 'updateExam']);
            Route::delete('/patients/{patient}/exams/{visit}', [ClinicalApiController::class, 'destroyExam']);
            Route::post('/patients/{patient}/notes', [ClinicalApiController::class, 'storeNote']);
            Route::delete('/patients/{patient}/notes/{note}', [ClinicalApiController::class, 'destroyNote']);

            Route::get('/panel', [PanelApiController::class, 'bootstrap']);
            Route::get('/reports', [PanelApiController::class, 'reports']);
            Route::get('/floor', [PanelApiController::class, 'floor']);
            Route::get('/followups', [PanelApiController::class, 'followups']);

            Route::get('/catalog', [ClinicOpsApiController::class, 'catalog']);
            Route::post('/patients/{patient}/documents', [ClinicOpsApiController::class, 'uploadDocuments']);
            Route::delete('/patients/{patient}/documents/{document}', [ClinicOpsApiController::class, 'destroyDocument']);
            Route::post('/patients/{patient}/prescriptions', [ClinicOpsApiController::class, 'storePrescription']);
            Route::post('/drawings', [ClinicOpsApiController::class, 'storeDrawing']);
            Route::post('/followups', [ClinicOpsApiController::class, 'storeFollowup']);
            Route::post('/followups/{followUp}/action', [ClinicOpsApiController::class, 'followupAction']);
            Route::get('/report-notes', [ClinicOpsApiController::class, 'reportNotes']);
            Route::post('/report-notes', [ClinicOpsApiController::class, 'storeReportNote']);
            Route::put('/report-notes/{note}', [ClinicOpsApiController::class, 'updateReportNote']);
            Route::delete('/report-notes/{note}', [ClinicOpsApiController::class, 'destroyReportNote']);
            Route::patch('/report-notes/{note}/print', [ClinicOpsApiController::class, 'toggleReportNotePrint']);
            Route::get('/messages', [ClinicOpsApiController::class, 'messages']);
            Route::post('/messages/read-all', [ClinicOpsApiController::class, 'markMessagesRead']);
            Route::post('/hospitals', [ClinicOpsApiController::class, 'storeHospital']);
            Route::patch('/hospitals/{hospital}', [ClinicOpsApiController::class, 'updateHospital']);
            Route::delete('/hospitals/{hospital}', [ClinicOpsApiController::class, 'destroyHospital']);
            Route::post('/drugs', [ClinicOpsApiController::class, 'storeDrug']);
            Route::patch('/drugs/{drug}', [ClinicOpsApiController::class, 'updateDrug']);
            Route::delete('/drugs/{drug}', [ClinicOpsApiController::class, 'destroyDrug']);
            Route::post('/surgery-types', [ClinicOpsApiController::class, 'storeSurgeryType']);
            Route::get('/times', [ClinicOpsApiController::class, 'times']);
            Route::post('/times', [ClinicOpsApiController::class, 'storeTime']);
            Route::patch('/times/{schedule}/sms', [ClinicOpsApiController::class, 'updateTimeSms']);
            Route::delete('/times/{schedule}', [ClinicOpsApiController::class, 'destroyTime']);
            Route::get('/users', [ClinicOpsApiController::class, 'users']);
            Route::post('/users', [ClinicOpsApiController::class, 'storeUser']);
            Route::patch('/users/{user}', [ClinicOpsApiController::class, 'updateUser']);
            Route::delete('/users/{user}', [ClinicOpsApiController::class, 'destroyUser']);
            Route::put('/features', [ClinicOpsApiController::class, 'updateFeatures']);
            Route::put('/quick-links', [ClinicOpsApiController::class, 'updateQuickLinks']);
            Route::get('/waiting', [ClinicOpsApiController::class, 'waiting']);
            Route::post('/waiting', [ClinicOpsApiController::class, 'storeWaiting']);
            Route::patch('/waiting/{entry}', [ClinicOpsApiController::class, 'waitingStatus']);
            Route::post('/waiting/{entry}/convert', [ClinicOpsApiController::class, 'convertWaiting']);
            Route::get('/accounting', [ClinicOpsApiController::class, 'accounting']);
            Route::post('/accounting', [ClinicOpsApiController::class, 'storeAccounting']);
            Route::get('/approvals', [ClinicOpsApiController::class, 'approvals']);
            Route::post('/approvals/{appointment}/approve', [ClinicOpsApiController::class, 'approveBooking']);
            Route::post('/approvals/{appointment}/reject', [ClinicOpsApiController::class, 'rejectBooking']);
            Route::get('/prints', [ClinicOpsApiController::class, 'prints']);
            Route::get('/prints/{surgeryAppointment}/hub', [ClinicOpsApiController::class, 'printHub']);
            Route::get('/prints/{surgeryAppointment}/all', [ClinicOpsApiController::class, 'printAll']);
            Route::get('/prints/{surgeryAppointment}', [ClinicOpsApiController::class, 'printSheet']);
            Route::get('/prescriptions/{prescription}/print', [ClinicOpsApiController::class, 'printRx']);
            Route::post('/patients/{patient}/documents/{document}/rotate', [ClinicOpsApiController::class, 'rotateDocument']);
            Route::post('/voice', [ClinicOpsApiController::class, 'storeVoice']);
            Route::post('/patients/{patient}/photo', [ClinicOpsApiController::class, 'updatePatientPhoto']);
            Route::post('/reminders/send', [ClinicOpsApiController::class, 'sendReminders']);
            Route::put('/reminders/sms', [ClinicOpsApiController::class, 'toggleSms']);
            Route::get('/billing', [ClinicOpsApiController::class, 'billing']);
            Route::post('/billing/tariffs', [ClinicOpsApiController::class, 'storeTariff']);
            Route::post('/billing/records', [ClinicOpsApiController::class, 'storeBilling']);
            Route::get('/consent', [ClinicOpsApiController::class, 'consent']);
            Route::post('/consent/templates', [ClinicOpsApiController::class, 'storeConsentTemplate']);
            Route::post('/consent/records', [ClinicOpsApiController::class, 'storeConsent']);
            Route::get('/portal', [ClinicOpsApiController::class, 'portal']);
            Route::get('/quality', [ClinicOpsApiController::class, 'quality']);
            Route::get('/eye-chart', [ClinicOpsApiController::class, 'eyeChart']);
            Route::get('/rx-prints', [ClinicOpsApiController::class, 'rxPrints']);
            Route::get('/contacts', [ClinicOpsApiController::class, 'contacts']);
            Route::get('/contacts/vcf', [ClinicOpsApiController::class, 'contactsVcf']);
            Route::get('/patients-manage', [ClinicOpsApiController::class, 'managePatients']);
            Route::post('/patients/{patient}/secure-erase', [ClinicOpsApiController::class, 'secureErase']);
            Route::get('/program-groups', [ClinicOpsApiController::class, 'programGroups']);
            Route::post('/program-groups', [ClinicOpsApiController::class, 'storeProgramGroup']);
            Route::get('/followup-settings', [ClinicOpsApiController::class, 'followupSettings']);
            Route::post('/followup-settings/catalog', [ClinicOpsApiController::class, 'storeFollowupCatalog']);
            Route::patch('/followup-settings/catalog/{catalogItem}', [ClinicOpsApiController::class, 'updateFollowupCatalog']);
            Route::post('/followup-settings/templates', [ClinicOpsApiController::class, 'storeFollowupTemplate']);
            Route::patch('/followup-settings/templates/{template}', [ClinicOpsApiController::class, 'updateFollowupTemplate']);
            Route::delete('/followup-settings/templates/{template}', [ClinicOpsApiController::class, 'destroyFollowupTemplate']);
            Route::post('/followup-settings/templates/{template}/steps', [ClinicOpsApiController::class, 'storeFollowupStep']);
            Route::patch('/followup-settings/steps/{step}', [ClinicOpsApiController::class, 'updateFollowupStep']);
            Route::delete('/followup-settings/steps/{step}', [ClinicOpsApiController::class, 'destroyFollowupStep']);
            Route::get('/activity-logs', [ClinicOpsApiController::class, 'activityLogs']);
            Route::get('/communications', [ClinicOpsApiController::class, 'communications']);
            Route::put('/communications', [ClinicOpsApiController::class, 'updateCommunications']);
            Route::get('/brand', [ClinicOpsApiController::class, 'brand']);
            Route::put('/brand', [ClinicOpsApiController::class, 'updateBrand']);
            Route::get('/his', [ClinicOpsApiController::class, 'hisMonitor']);
            Route::delete('/surgery-types/{surgeryType}', [ClinicOpsApiController::class, 'destroySurgeryType']);
            Route::post('/surgery-types/{surgeryType}/subtypes', [ClinicOpsApiController::class, 'storeSubtype']);

            Route::get('/ready-answers', [ClinicToolsApiController::class, 'readyAnswers']);
            Route::post('/ready-answers', [ClinicToolsApiController::class, 'storeReadyAnswer']);
            Route::post('/ready-answers/send', [ClinicToolsApiController::class, 'sendReadyAnswer']);
            Route::get('/ready-answers/bookings/{patient}', [ClinicToolsApiController::class, 'readyAnswerBookings']);
            Route::put('/ready-answers/{readyAnswer}', [ClinicToolsApiController::class, 'updateReadyAnswer']);
            Route::delete('/ready-answers/{readyAnswer}', [ClinicToolsApiController::class, 'destroyReadyAnswer']);
            Route::post('/ready-answers/{readyAnswer}/pin', [ClinicToolsApiController::class, 'pinReadyAnswer']);
            Route::post('/ready-answers/{readyAnswer}/duplicate', [ClinicToolsApiController::class, 'duplicateReadyAnswer']);
            Route::post('/ready-answers/{readyAnswer}/used', [ClinicToolsApiController::class, 'markReadyAnswerUsed']);
            Route::get('/checklist-template', [ClinicToolsApiController::class, 'checklistTemplate']);
            Route::put('/checklist-template', [ClinicToolsApiController::class, 'updateChecklistTemplate']);
            Route::post('/surgeries/{surgeryAppointment}/checklist/ensure', [ClinicToolsApiController::class, 'ensureChecklist']);
            Route::get('/checklists/{checklist}', [ClinicToolsApiController::class, 'showChecklist']);
            Route::patch('/checklists/{checklist}/items/{item}/toggle', [ClinicToolsApiController::class, 'toggleChecklistItem']);
            Route::post('/checklists/{checklist}/items', [ClinicToolsApiController::class, 'storeChecklistItem']);
            Route::put('/checklists/{checklist}/items/{item}', [ClinicToolsApiController::class, 'updateChecklistItem']);
            Route::delete('/checklists/{checklist}/items/{item}', [ClinicToolsApiController::class, 'destroyChecklistItem']);
            Route::post('/checklists/{checklist}/confirm', [ClinicToolsApiController::class, 'confirmChecklist']);
            Route::get('/system', [ClinicToolsApiController::class, 'system']);
            Route::post('/system/backup', [ClinicToolsApiController::class, 'runBackup']);
            Route::post('/system/restore', [ClinicToolsApiController::class, 'restoreBackup']);
            Route::get('/system/backup/{file}/download', [ClinicToolsApiController::class, 'downloadBackup'])->where('file', 'backup_[\w\-\.]+\.(sqlite|sql)');
            Route::put('/system/privacy', [ClinicToolsApiController::class, 'updatePrivacy']);
            Route::get('/help', [ClinicToolsApiController::class, 'help']);
            Route::get('/support', [ClinicToolsApiController::class, 'support']);
            Route::put('/support', [ClinicToolsApiController::class, 'updateSupport']);
        });
    });
});
