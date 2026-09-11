<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentBoardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicFloorController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\FollowUpReminderController;
use App\Http\Controllers\FollowUpSettingsController;
use App\Http\Controllers\PatientFollowUpController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\InternalNoteController;
use App\Http\Controllers\MedicalDocumentController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportNoteController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StaffMessageController;
use App\Http\Controllers\SurgeryAppointmentController;
use App\Http\Controllers\SurgeryPrintController;
use App\Http\Controllers\SurgeryTypeController;
use App\Http\Controllers\TimeController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/pwa/icon-{size}.png', [PwaController::class, 'icon'])->whereNumber('size')->name('pwa.icon');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

Route::middleware(['auth', 'verified', 'role:doctor,admin,assistant'])->group(function () {
    Route::get('/dashboard', [PatientController::class, 'index'])->name('dashboard');
    Route::get('/help', [\App\Http\Controllers\HelpController::class, 'index'])->name('help.index');

    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::get('/patients/lookup-national-code', [PatientController::class, 'lookupByNationalCode'])->name('patients.lookup-national-code');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::post('/patients/import-json', [PatientController::class, 'importJson'])->name('patients.import-json');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}/mobile-secondary', [PatientController::class, 'updateMobileSecondary'])->name('patients.mobile-secondary');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('/patients/{patient}/photo', [PatientController::class, 'updatePhoto'])->name('patients.photo.update');
    Route::delete('/patients/{patient}/photo', [PatientController::class, 'destroyPhoto'])->name('patients.photo.destroy');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');

    Route::post('/patients/{patient}/documents', [MedicalDocumentController::class, 'store'])->name('documents.store');
    Route::post('/patients/{patient}/documents/{document}/rotate', [MedicalDocumentController::class, 'rotate'])->name('documents.rotate');
    Route::delete('/patients/{patient}/documents/{document}', [MedicalDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/patients/{patient}/follow-ups', [FollowUpReminderController::class, 'store'])->name('followups.store');
    Route::delete('/patients/{patient}/follow-ups/{reminder}', [FollowUpReminderController::class, 'destroy'])->name('followups.destroy');

    Route::get('/follow-ups', [PatientFollowUpController::class, 'index'])->name('followups.index');
    Route::get('/follow-ups/alert-summary', [PatientFollowUpController::class, 'alertSummary'])->name('followups.alert-summary');
    Route::get('/messages', [StaffMessageController::class, 'index'])->name('staff-messages.index');
    Route::get('/messages/alert-summary', [StaffMessageController::class, 'alertSummary'])->name('staff-messages.alert-summary');
    Route::post('/messages/read-all', [StaffMessageController::class, 'markAllRead'])->name('staff-messages.read-all');
    Route::post('/follow-ups', [PatientFollowUpController::class, 'store'])->name('followups.manual');
    Route::post('/follow-ups/bulk', [PatientFollowUpController::class, 'storeBulk'])->name('followups.bulk');
    Route::put('/follow-ups/{followUp}', [PatientFollowUpController::class, 'update'])->name('followups.update');
    Route::post('/follow-ups/{followUp}/start', [PatientFollowUpController::class, 'start'])->name('followups.start');
    Route::post('/follow-ups/{followUp}/complete', [PatientFollowUpController::class, 'complete'])->name('followups.complete');
    Route::post('/follow-ups/{followUp}/fail', [PatientFollowUpController::class, 'fail'])->name('followups.fail');
    Route::post('/follow-ups/{followUp}/cancel', [PatientFollowUpController::class, 'cancel'])->name('followups.cancel');
    Route::post('/follow-ups/{followUp}/retry', [PatientFollowUpController::class, 'retry'])->name('followups.retry');

    Route::get('/appointments/board', [AppointmentBoardController::class, 'index'])->name('appointments.board');
    Route::get('/clinic', [ClinicFloorController::class, 'index'])->name('clinic.floor');
    Route::get('/clinic/live', [ClinicFloorController::class, 'live'])->name('clinic.floor.live');
    Route::get('/appointments/slots', [AppointmentController::class, 'slots'])->name('appointments.slots');
    Route::get('/appointments/visit-calendar', [AppointmentController::class, 'visitCalendar'])->name('appointments.visit-calendar');
    Route::get('/appointments/surgery-options', [AppointmentController::class, 'surgeryOptions'])->name('appointments.surgery-options');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/notes', [ReportNoteController::class, 'show'])->name('reports.notes.show');
    Route::post('/reports/notes', [ReportNoteController::class, 'store'])->name('reports.notes.store');
    Route::put('/reports/notes/{note}', [ReportNoteController::class, 'update'])->name('reports.notes.update');
    Route::post('/reports/notes/{note}/update', [ReportNoteController::class, 'update'])->name('reports.notes.update.post');
    Route::post('/reports/notes/{note}/delete', [ReportNoteController::class, 'destroy'])->name('reports.notes.destroy');
    Route::delete('/reports/notes/{note}', [ReportNoteController::class, 'destroy']);
    Route::post('/reports/notes/{note}/print', [ReportNoteController::class, 'togglePrint'])->name('reports.notes.print');
    Route::put('/reports/mobile-secondary', [ReportController::class, 'updateMobileSecondary'])->name('reports.mobile-secondary');
    Route::post('/reminders/send', [ReminderController::class, 'send'])->name('reminders.send');
    Route::put('/reminders/sms-toggle', [ReminderController::class, 'toggleSmsGlobal'])->name('reminders.sms-toggle');
    Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
    Route::put('/toolbox-prefs', [\App\Http\Controllers\ToolboxPrefsController::class, 'update'])->name('toolbox-prefs.update');

    Route::get('/ready-answers', [\App\Http\Controllers\ReadyAnswerController::class, 'index'])->name('ready-answers.index');
    Route::post('/ready-answers', [\App\Http\Controllers\ReadyAnswerController::class, 'store'])->name('ready-answers.store');
    Route::put('/ready-answers/{readyAnswer}', [\App\Http\Controllers\ReadyAnswerController::class, 'update'])->name('ready-answers.update');
    Route::delete('/ready-answers/{readyAnswer}', [\App\Http\Controllers\ReadyAnswerController::class, 'destroy'])->name('ready-answers.destroy');
    Route::post('/ready-answers/send', [\App\Http\Controllers\ReadyAnswerController::class, 'send'])->name('ready-answers.send');
    Route::post('/ready-answers/{readyAnswer}/pin', [\App\Http\Controllers\ReadyAnswerController::class, 'togglePin'])->name('ready-answers.pin');
    Route::post('/ready-answers/{readyAnswer}/duplicate', [\App\Http\Controllers\ReadyAnswerController::class, 'duplicate'])->name('ready-answers.duplicate');
    Route::post('/ready-answers/{readyAnswer}/used', [\App\Http\Controllers\ReadyAnswerController::class, 'markUsed'])->name('ready-answers.used');
    Route::get('/ready-answers/bookings/{patient}', [\App\Http\Controllers\ReadyAnswerController::class, 'bookings'])->name('ready-answers.bookings');

    Route::get('/prints', [SurgeryPrintController::class, 'index'])->name('prints.index');
    Route::get('/surgery-appointments/{surgeryAppointment}/prints', [SurgeryPrintController::class, 'hub'])->name('surgery-appointments.prints');
    Route::get('/surgery-appointments/{surgeryAppointment}/prints/all', [SurgeryPrintController::class, 'printAll'])->name('surgery-appointments.prints.all');
    Route::get('/surgery-appointments/{surgeryAppointment}/prints/{type}', [SurgeryPrintController::class, 'show'])->name('surgery-appointments.print');
    Route::post('/surgery-appointments/{surgeryAppointment}/prints/hospital-meta', [SurgeryPrintController::class, 'saveHospitalMeta'])->name('surgery-appointments.prints.hospital-meta');
    Route::post('/surgery-appointments/{surgeryAppointment}/prints/hospital', [SurgeryPrintController::class, 'assignHospital'])->name('surgery-appointments.prints.hospital');


    Route::get('/visit/register', [AppointmentController::class, 'register'])->name('appointments.register');
    Route::post('/visit/register', [AppointmentController::class, 'registerStore'])->name('appointments.register.store');
    Route::get('/patients/{patient}/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/patients/{patient}/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

    Route::get('/surgery/register', [SurgeryAppointmentController::class, 'register'])->name('surgery-appointments.register');
    Route::post('/surgery/register', [SurgeryAppointmentController::class, 'registerStore'])->name('surgery-appointments.register.store');
    Route::get('/surgery/cooldown-check', [SurgeryAppointmentController::class, 'cooldownCheck'])->name('surgery-appointments.cooldown-check');
    Route::get('/patients/{patient}/surgery-appointments/create', [SurgeryAppointmentController::class, 'create'])->name('surgery-appointments.create');
    Route::post('/patients/{patient}/surgery-appointments', [SurgeryAppointmentController::class, 'store'])->name('surgery-appointments.store');
    Route::get('/surgery-appointments/{surgeryAppointment}/edit', [SurgeryAppointmentController::class, 'edit'])->name('surgery-appointments.edit');
    Route::put('/surgery-appointments/{surgeryAppointment}', [SurgeryAppointmentController::class, 'update'])->name('surgery-appointments.update');
    Route::patch('/surgery-appointments/{surgeryAppointment}/status', [SurgeryAppointmentController::class, 'updateStatus'])->name('surgery-appointments.status');

    Route::prefix('modules')->name('modules.')->middleware('role:doctor,admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Modules\ModulesHubController::class, 'index'])->name('hub');

        Route::middleware('feature:features.accounting')->group(function () {
            Route::get('/accounting', [\App\Http\Controllers\Modules\AccountingController::class, 'index'])->name('accounting.index');
            Route::post('/accounting', [\App\Http\Controllers\Modules\AccountingController::class, 'store'])->name('accounting.store');
            Route::get('/accounting/export', [\App\Http\Controllers\Modules\AccountingController::class, 'export'])->name('accounting.export');
            Route::get('/accounting/cards/export', [\App\Http\Controllers\Modules\AccountingCardController::class, 'export'])->name('accounting.cards.export');

            Route::middleware('role:doctor,admin')->group(function () {
                Route::post('/accounting/cards', [\App\Http\Controllers\Modules\AccountingCardController::class, 'store'])->name('accounting.cards.store');
                Route::post('/accounting/cards/presets', [\App\Http\Controllers\Modules\AccountingCardController::class, 'presets'])->name('accounting.cards.presets');
                Route::put('/accounting/cards/{reportCard}', [\App\Http\Controllers\Modules\AccountingCardController::class, 'update'])->name('accounting.cards.update');
                Route::delete('/accounting/cards/{reportCard}', [\App\Http\Controllers\Modules\AccountingCardController::class, 'destroy'])->name('accounting.cards.destroy');
            });
        });

        Route::middleware('feature:features.billing_insurance')->group(function () {
            Route::get('/billing', [\App\Http\Controllers\Modules\BillingController::class, 'index'])->name('billing.index');
            Route::post('/billing/tariffs', [\App\Http\Controllers\Modules\BillingController::class, 'storeTariff'])->name('billing.tariffs.store');
            Route::post('/billing/records', [\App\Http\Controllers\Modules\BillingController::class, 'storeRecord'])->name('billing.records.store');
            Route::post('/billing/from-billable', [\App\Http\Controllers\Modules\BillingController::class, 'storeFromBillable'])->name('billing.from-billable');
        });

        Route::middleware('feature:features.consent_forms')->group(function () {
            Route::get('/consent', [\App\Http\Controllers\Modules\ConsentController::class, 'index'])->name('consent.index');
            Route::post('/consent/templates', [\App\Http\Controllers\Modules\ConsentController::class, 'storeTemplate'])->name('consent.templates.store');
            Route::post('/consent/records', [\App\Http\Controllers\Modules\ConsentController::class, 'storeConsent'])->name('consent.records.store');
            Route::get('/consent/{consent}/print', [\App\Http\Controllers\Modules\ConsentController::class, 'print'])->name('consent.print');
        });

        Route::middleware('feature:features.patient_portal')->group(function () {
            Route::get('/portal', [\App\Http\Controllers\Modules\PortalAdminController::class, 'index'])->name('portal.index');
        });

        Route::middleware('feature:features.waiting_list')->group(function () {
            Route::get('/waiting', [\App\Http\Controllers\Modules\WaitingListController::class, 'index'])->name('waiting.index');
            Route::post('/waiting', [\App\Http\Controllers\Modules\WaitingListController::class, 'store'])->name('waiting.store');
            Route::post('/waiting/{entry}/convert', [\App\Http\Controllers\Modules\WaitingListController::class, 'convert'])->name('waiting.convert');
            Route::patch('/waiting/{entry}/status', [\App\Http\Controllers\Modules\WaitingListController::class, 'updateStatus'])->name('waiting.status');
        });

        Route::middleware('feature:features.online_booking_approval')->group(function () {
            Route::get('/approval', [\App\Http\Controllers\Modules\BookingApprovalController::class, 'index'])->name('approval.index');
            Route::post('/approval/{appointment}/approve', [\App\Http\Controllers\Modules\BookingApprovalController::class, 'approve'])->name('approval.approve');
            Route::post('/approval/{appointment}/reject', [\App\Http\Controllers\Modules\BookingApprovalController::class, 'reject'])->name('approval.reject');
        });

        Route::middleware('feature:features.quality_dashboard')->group(function () {
            Route::get('/quality', [\App\Http\Controllers\Modules\QualityDashboardController::class, 'index'])->name('quality.index');
        });

        Route::middleware('feature:features.eye_chart')->group(function () {
            Route::get('/eye-chart', [\App\Http\Controllers\Modules\EyeChartController::class, 'index'])->name('eye-chart.index');
        });

        Route::middleware('feature:features.structured_prescriptions')->group(function () {
            Route::get('/prescriptions', [\App\Http\Controllers\Modules\StructuredPrescriptionController::class, 'index'])->name('prescriptions.index');
            Route::get('/prescriptions/{prescription}/print', [\App\Http\Controllers\Modules\StructuredPrescriptionController::class, 'print'])->name('prescriptions.print');
        });
    });

    Route::get('/patients/{patient}/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])
        ->name('prescriptions.print');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/times', [TimeController::class, 'index'])->name('settings.times');
    Route::get('/settings/hospitals', [HospitalController::class, 'index'])->name('settings.hospitals');
    Route::get('/settings/surgery-types', [SurgeryTypeController::class, 'index'])->name('settings.surgery-types');
    Route::get('/settings/surgery-program-groups', [\App\Http\Controllers\SurgeryProgramGroupController::class, 'index'])->name('settings.surgery-program-groups');
    Route::get('/settings/follow-ups', [FollowUpSettingsController::class, 'index'])->name('settings.follow-ups');
    Route::get('/settings/drugs', [DrugController::class, 'index'])->name('settings.drugs');
    Route::get('/settings/contacts', [\App\Http\Controllers\ContactExportController::class, 'index'])->name('settings.contacts');
    Route::get('/settings/contacts-export', [\App\Http\Controllers\ContactExportController::class, 'json'])->name('settings.contacts.export');
    Route::get('/settings/contacts.vcf', [\App\Http\Controllers\ContactExportController::class, 'vcf'])->name('settings.contacts.vcf');
    Route::get('/settings/contacts-sync', [\App\Http\Controllers\ContactExportController::class, 'vcf'])->name('settings.contacts.sync');
    Route::get('/settings/patients', [PatientController::class, 'manage'])->name('settings.patients');
    Route::delete('/patients/{patient}/secure-erase', [PatientController::class, 'destroy'])->name('patients.secure-erase');

    Route::post('/settings/quick/hospitals', [\App\Http\Controllers\SettingsQuickAddController::class, 'storeHospital'])->name('settings.quick.hospital');
    Route::post('/settings/quick/surgery-types', [\App\Http\Controllers\SettingsQuickAddController::class, 'storeSurgeryType'])->name('settings.quick.surgery-type');
    Route::post('/settings/quick/surgery-subtypes', [\App\Http\Controllers\SettingsQuickAddController::class, 'storeSurgerySubtype'])->name('settings.quick.surgery-subtype');

    Route::post('/times', [TimeController::class, 'store'])->name('times.store');
    Route::post('/times/bulk', [TimeController::class, 'bulkStore'])->name('times.bulk-store');
    Route::delete('/times/{schedule}', [TimeController::class, 'destroy'])->name('times.destroy');
    Route::post('/times/bulk-destroy', [TimeController::class, 'bulkDestroy'])->name('times.bulk-destroy');
    Route::post('/times/bulk-sms', [TimeController::class, 'bulkUpdateSms'])->name('times.bulk-sms');
    Route::patch('/times/{schedule}/sms', [TimeController::class, 'updateSms'])->name('times.update-sms');
    Route::patch('/times/{schedule}/capacity', [TimeController::class, 'updateCapacity'])->name('times.update-capacity');
    Route::post('/times/group-days', [TimeController::class, 'updateGroupDay'])->name('times.group-day');
    Route::post('/hospitals', [HospitalController::class, 'store'])->name('hospitals.store');
    Route::put('/hospitals/{hospital}', [HospitalController::class, 'update'])->name('hospitals.update');
    Route::delete('/hospitals/{hospital}', [HospitalController::class, 'destroy'])->name('hospitals.destroy');

    Route::post('/drugs', [DrugController::class, 'store'])->name('drugs.store');
    Route::put('/drugs/{drug}', [DrugController::class, 'update'])->name('drugs.update');
    Route::delete('/drugs/{drug}', [DrugController::class, 'destroy'])->name('drugs.destroy');

    Route::post('/surgery-types', [SurgeryTypeController::class, 'store'])->name('surgery-types.store');
    Route::put('/surgery-types/{surgeryType}', [SurgeryTypeController::class, 'update'])->name('surgery-types.update');
    Route::put('/surgery-types/{surgeryType}/cooldown', [SurgeryTypeController::class, 'updateCooldown'])->name('surgery-types.cooldown.update');
    Route::delete('/surgery-types/{surgeryType}', [SurgeryTypeController::class, 'destroy'])->name('surgery-types.destroy');
    Route::post('/surgery-types/{surgeryType}/subtypes', [SurgeryTypeController::class, 'storeSubtype'])->name('surgery-types.subtypes.store');
    Route::put('/surgery-subtypes/{surgerySubtype}', [SurgeryTypeController::class, 'updateSubtype'])->name('surgery-types.subtypes.update');
    Route::delete('/surgery-subtypes/{surgerySubtype}', [SurgeryTypeController::class, 'destroySubtype'])->name('surgery-types.subtypes.destroy');
    Route::post('/surgery-program-groups', [\App\Http\Controllers\SurgeryProgramGroupController::class, 'store'])->name('surgery-program-groups.store');
    Route::post('/surgery-program-groups/{surgeryProgramGroup}/update', [\App\Http\Controllers\SurgeryProgramGroupController::class, 'update'])->name('surgery-program-groups.update.post');
    Route::post('/surgery-program-groups/{surgeryProgramGroup}/delete', [\App\Http\Controllers\SurgeryProgramGroupController::class, 'destroy'])->name('surgery-program-groups.destroy.post');

    Route::post('/settings/follow-ups/catalog', [FollowUpSettingsController::class, 'storeCatalog'])->name('settings.follow-ups.catalog.store');
    Route::put('/settings/follow-ups/catalog/{catalogItem}', [FollowUpSettingsController::class, 'updateCatalog'])->name('settings.follow-ups.catalog.update');
    Route::post('/settings/follow-ups/templates', [FollowUpSettingsController::class, 'storeTemplate'])->name('settings.follow-ups.templates.store');
    Route::put('/settings/follow-ups/templates/{template}', [FollowUpSettingsController::class, 'updateTemplate'])->name('settings.follow-ups.templates.update');
    Route::delete('/settings/follow-ups/templates/{template}', [FollowUpSettingsController::class, 'destroyTemplate'])->name('settings.follow-ups.templates.destroy');
    Route::post('/settings/follow-ups/templates/{template}/steps', [FollowUpSettingsController::class, 'storeStep'])->name('settings.follow-ups.steps.store');
    Route::put('/settings/follow-ups/steps/{step}', [FollowUpSettingsController::class, 'updateStep'])->name('settings.follow-ups.steps.update');
    Route::delete('/settings/follow-ups/steps/{step}', [FollowUpSettingsController::class, 'destroyStep'])->name('settings.follow-ups.steps.destroy');

    Route::get('/surgery-checklist-templates', [\App\Http\Controllers\SurgeryChecklistTemplateController::class, 'show'])->name('surgery-checklist-templates.show');
    Route::put('/surgery-checklist-templates', [\App\Http\Controllers\SurgeryChecklistTemplateController::class, 'update'])->name('surgery-checklist-templates.update');

    Route::post('/surgery-appointments/{surgeryAppointment}/checklist/ensure', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'ensure'])->name('surgery-checklists.ensure');
    Route::get('/surgery-checklists/{checklist}', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'show'])->name('surgery-checklists.show');
    Route::patch('/surgery-checklists/{checklist}/items/{item}/toggle', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'toggle'])->name('surgery-checklists.items.toggle');
    Route::post('/surgery-checklists/{checklist}/items', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'storeItem'])->name('surgery-checklists.items.store');
    Route::put('/surgery-checklists/{checklist}/items/{item}', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'updateItem'])->name('surgery-checklists.items.update');
    Route::delete('/surgery-checklists/{checklist}/items/{item}', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'destroyItem'])->name('surgery-checklists.items.destroy');
    Route::post('/surgery-checklists/{checklist}/confirm', [\App\Http\Controllers\PatientSurgeryChecklistController::class, 'confirm'])->name('surgery-checklists.confirm');

    // سازگاری لینک‌های قدیمی
    Route::redirect('/times', '/settings/times')->name('times.index');
    Route::redirect('/hospitals', '/settings/hospitals')->name('hospitals.index');
    Route::redirect('/surgery-types', '/settings/surgery-types')->name('surgery-types.index');
    Route::redirect('/drugs', '/settings/drugs')->name('drugs.index');
});

Route::middleware(['auth', 'verified', 'role:doctor,admin'])->group(function () {
    Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])->name('visits.store');
    Route::put('/patients/{patient}/visits/{visit}', [VisitController::class, 'update'])->name('visits.update');
    Route::delete('/patients/{patient}/visits/{visit}', [VisitController::class, 'destroy'])->name('visits.destroy');
    Route::post('/visits/drawing', [VisitController::class, 'storeDrawing'])->name('visits.store-drawing');
    Route::post('/visits/voice', [VisitController::class, 'storeVoice'])->name('visits.store-voice');
    Route::post('/patients/{patient}/notes', [InternalNoteController::class, 'store'])->name('notes.store');
    Route::put('/patients/{patient}/notes/{note}', [InternalNoteController::class, 'update'])->name('notes.update');
    Route::delete('/patients/{patient}/notes/{note}', [InternalNoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/patients/{patient}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    Route::put('/patients/{patient}/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->name('prescriptions.update');
    Route::delete('/patients/{patient}/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin,doctor'])->group(function () {
    Route::get('/admin', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.index');
    Route::put('/admin/layout', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'updateLayout'])->name('admin.layout.update');
    Route::post('/admin/layout/reset', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'resetLayout'])->name('admin.layout.reset');
    Route::get('/admin/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/communications', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'communications'])->name('admin.settings.communications');
    Route::put('/admin/communications', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateCommunications'])->name('admin.settings.communications.update');
    Route::get('/admin/brand', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'brand'])->name('admin.settings.brand');
    Route::put('/admin/brand', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateBrand'])->name('admin.settings.brand.update');
    Route::put('/admin/brand/print-templates', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updatePrintTemplates'])->name('admin.settings.brand.print-templates');
    Route::get('/admin/system', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'system'])->name('admin.settings.system');
    Route::post('/admin/system/backup', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'runBackup'])->name('admin.settings.backup');
    Route::get('/admin/system/backup/{file}/download', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'downloadBackup'])->name('admin.settings.backup.download');
    Route::post('/admin/system/backup/restore', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'restoreBackup'])->name('admin.settings.backup.restore');
    Route::put('/admin/system/privacy', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updatePrivacy'])->name('admin.settings.privacy.update');
    Route::get('/admin/support', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'support'])->name('admin.settings.support');
    Route::put('/admin/support', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateSupport'])->name('admin.settings.support.update');
    Route::get('/admin/features', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'features'])->name('admin.settings.features');
    Route::put('/admin/features', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateFeatures'])->name('admin.settings.features.update');
    Route::get('/admin/quick-links', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'quickLinks'])->name('admin.settings.quick-links');
    Route::put('/admin/quick-links', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateQuickLinks'])->name('admin.settings.quick-links.update');

    Route::get('/admin/his', [\App\Http\Controllers\Admin\HisSyncMonitorController::class, 'index'])->name('admin.his.index');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');

    Route::get('/admin/modules', [\App\Http\Controllers\Modules\ModulesHubController::class, 'index'])->name('admin.modules');
});

Route::middleware('auth')->group(function () {
    Route::get('/my-profile', [PatientController::class, 'myProfile'])
        ->middleware('role:patient')
        ->name('my-profile');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
