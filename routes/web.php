<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CkeditorUploadController;
use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\AssetCheckController;
use App\Http\Controllers\AssetReceptionController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BuildingBlockController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DocumentLookupController;
use App\Http\Controllers\DocumentRecordController;
use App\Http\Controllers\IncidentRecordController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExternalCheckinController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\IncidentCategoryController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\LecturerPortalController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\MonitoringOverviewController;
use App\Http\Controllers\MonitoringScheduleController;
use App\Http\Controllers\OnlineClassController;
use App\Http\Controllers\PermissionSettingsController;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemParameterController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\PublicExtensionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RecognitionController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentViolationController;
use Illuminate\Support\Facades\Route;

Route::get('/poll/{poll}', [PublicExtensionController::class, 'poll'])->name('poll.show');
Route::get('/exam/{exam}', [PublicExtensionController::class, 'exam'])->name('exam.show');
Route::get('/monitoring/online-report/{id}', [PublicExtensionController::class, 'onlineReport'])->name('online-report.show');
Route::get('/discussion', [DiscussionController::class, 'index'])->name('discussion.index');
Route::post('/discussion/{section}/comment', [DiscussionController::class, 'storeComment'])->name('discussion.comment');
Route::put('/discussion/{section}', [DiscussionController::class, 'updateSection'])->name('discussion.update');
Route::delete('/discussion/{section}', [DiscussionController::class, 'destroySection'])->name('discussion.destroy');
Route::delete('/discussion/{section}/comments/{commentId}', [DiscussionController::class, 'destroyComment'])->name('discussion.comment.destroy');

Route::get('/lecturer-portal', [LecturerPortalController::class, 'index'])->name('lecturer-portal.index');
Route::post('/lecturer-portal/auth/google', [LecturerPortalController::class, 'authGoogle'])->name('lecturer-portal.auth.google');
Route::post('/lecturer-portal/logout', [LecturerPortalController::class, 'logout'])->name('lecturer-portal.logout');
Route::get('/lecturer-portal/me', [LecturerPortalController::class, 'me'])->name('lecturer-portal.me');
Route::get('/lecturer-portal/search-class', [LecturerPortalController::class, 'searchClass'])->name('lecturer-portal.search');
Route::post('/lecturer-portal/evidence/upload', [LecturerPortalController::class, 'uploadEvidence'])->name('lecturer-portal.evidence.upload');
Route::post('/lecturer-portal', [LecturerPortalController::class, 'submit'])->name('lecturer-portal.submit');

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified', 'route.permission'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/schedule-lookup', [DashboardController::class, 'scheduleLookup'])->name('dashboard.schedule-lookup');
    Route::get('/dashboard/schedule-export', [DashboardController::class, 'exportSchedule'])->name('dashboard.schedule-export');
    Route::get('/dashboard/filter-presets', [DashboardController::class, 'filterPresets'])->name('dashboard.filter-presets');
    Route::put('/dashboard/filter-presets', [DashboardController::class, 'saveFilterPresets'])->name('dashboard.filter-presets.save');
    Route::post('/dashboard/shift-schedule', [DashboardController::class, 'uploadShiftSchedule'])->name('dashboard.shift-schedule.store');
    Route::get('/dashboard/shift-schedule/file', [DashboardController::class, 'showShiftSchedule'])->name('dashboard.shift-schedule.file');
    Route::post('/dashboard/period-schedule', [DashboardController::class, 'uploadPeriodSchedule'])->name('dashboard.period-schedule.store');
    Route::get('/dashboard/period-schedule/file', [DashboardController::class, 'showPeriodSchedule'])->name('dashboard.period-schedule.file');
    Route::post('/dashboard/birthdays/{employee}/greeting', [DashboardController::class, 'sendBirthdayGreeting'])->name('dashboard.birthdays.send');

    Route::get('imports/status/{id}', [\App\Http\Controllers\ImportStatusController::class, 'show'])->name('imports.status');

    Route::get('personnel/employees-export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('personnel/employees-list', [EmployeeController::class, 'list'])->name('employees.list');
    Route::post('personnel/employees-import-preview', [EmployeeController::class, 'importPreview'])->name('employees.import-preview');
    Route::post('personnel/employees-import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::resource('personnel/employees', EmployeeController::class)->names('employees');
    Route::get('personnel/departments-export', [DepartmentController::class, 'export'])->name('departments.export');
    Route::post('personnel/departments-import-preview', [DepartmentController::class, 'importPreview'])->name('departments.import-preview');
    Route::post('personnel/departments-import', [DepartmentController::class, 'import'])->name('departments.import');
    Route::resource('personnel/departments', DepartmentController::class)->names('departments');
    Route::get('personnel/positions-export', [PositionController::class, 'export'])->name('positions.export');
    Route::post('personnel/positions-import-preview', [PositionController::class, 'importPreview'])->name('positions.import-preview');
    Route::post('personnel/positions-import', [PositionController::class, 'import'])->name('positions.import');
    Route::resource('personnel/positions', PositionController::class)->names('positions');
    Route::get('personnel/roles-export', [RoleController::class, 'export'])->name('roles.export');
    Route::post('personnel/roles-import-preview', [RoleController::class, 'importPreview'])->name('roles.import-preview');
    Route::post('personnel/roles-import', [RoleController::class, 'import'])->name('roles.import');
    Route::resource('personnel/roles', RoleController::class)->names('roles');
    Route::get('personnel/students-export', [StudentController::class, 'export'])->name('students.export');
    Route::get('personnel/students-list', [StudentController::class, 'list'])->name('students.list');
    Route::post('personnel/students-import-preview', [StudentController::class, 'importPreview'])->name('students.import-preview');
    Route::post('personnel/students-import', [StudentController::class, 'import'])->name('students.import');
    Route::resource('personnel/students', StudentController::class)->names('students');
    Route::get('personnel/lecturers-export', [LecturerController::class, 'export'])->name('lecturers.export');
    Route::post('personnel/lecturers-import-preview', [LecturerController::class, 'importPreview'])->name('lecturers.import-preview');
    Route::post('personnel/lecturers-import', [LecturerController::class, 'import'])->name('lecturers.import');
    Route::resource('personnel/lecturers', LecturerController::class)->names('lecturers');
    Route::get('personnel/building-blocks-export', [BuildingBlockController::class, 'export'])->name('building-blocks.export');
    Route::post('personnel/building-blocks-import-preview', [BuildingBlockController::class, 'importPreview'])->name('building-blocks.import-preview');
    Route::post('personnel/building-blocks-import', [BuildingBlockController::class, 'import'])->name('building-blocks.import');
    Route::resource('personnel/building-blocks', BuildingBlockController::class)->names('building-blocks');
    Route::get('personnel/classrooms-export', [ClassroomController::class, 'export'])->name('classrooms.export');
    Route::post('personnel/classrooms-import-preview', [ClassroomController::class, 'importPreview'])->name('classrooms.import-preview');
    Route::post('personnel/classrooms-import', [ClassroomController::class, 'import'])->name('classrooms.import');
    Route::resource('personnel/classrooms', ClassroomController::class)->names('classrooms');
    Route::get('personnel/gifts-export', [GiftController::class, 'export'])->name('gifts.export');
    Route::post('personnel/gifts-import-preview', [GiftController::class, 'importPreview'])->name('gifts.import-preview');
    Route::post('personnel/gifts-import', [GiftController::class, 'import'])->name('gifts.import');
    Route::resource('personnel/gifts', GiftController::class)->names('gifts');
    Route::get('personnel/recognitions-export', [RecognitionController::class, 'export'])->name('recognitions.export');
    Route::post('personnel/recognitions-import-preview', [RecognitionController::class, 'importPreview'])->name('recognitions.import-preview');
    Route::post('personnel/recognitions-import', [RecognitionController::class, 'import'])->name('recognitions.import');
    Route::resource('personnel/recognitions', RecognitionController::class)->names('recognitions');
    Route::get('personnel/incident-categories-export', [IncidentCategoryController::class, 'export'])->name('incident-categories.export');
    Route::post('personnel/incident-categories-import-preview', [IncidentCategoryController::class, 'importPreview'])->name('incident-categories.import-preview');
    Route::post('personnel/incident-categories-import', [IncidentCategoryController::class, 'import'])->name('incident-categories.import');
    Route::resource('personnel/incident-categories', IncidentCategoryController::class)->names('incident-categories');
    Route::get('personnel/document-types-export', [DocumentTypeController::class, 'export'])->name('document-types.export');
    Route::post('personnel/document-types-import-preview', [DocumentTypeController::class, 'importPreview'])->name('document-types.import-preview');
    Route::post('personnel/document-types-import', [DocumentTypeController::class, 'import'])->name('document-types.import');
    Route::resource('personnel/document-types', DocumentTypeController::class)->names('document-types');

    Route::post('monitoring/student-violations/compare-faces', [StudentViolationController::class, 'compareFaces'])->name('student-violations.compare-faces');
    Route::post('monitoring/student-violations/extract-card', [StudentViolationController::class, 'extractCardInfo'])->name('student-violations.extract-card');
    Route::get('monitoring/student-violations-export', [StudentViolationController::class, 'export'])->name('student-violations.export');
    Route::post('monitoring/student-violations-import-preview', [StudentViolationController::class, 'importPreview'])->name('student-violations.import-preview');
    Route::post('monitoring/student-violations-import', [StudentViolationController::class, 'import'])->name('student-violations.import');
    Route::resource('monitoring/student-violations', StudentViolationController::class)->names('student-violations');
    Route::get('monitoring/requests-export', [ServiceRequestController::class, 'export'])->name('requests.export');
    Route::post('monitoring/requests-import-preview', [ServiceRequestController::class, 'importPreview'])->name('requests.import-preview');
    Route::post('monitoring/requests-import', [ServiceRequestController::class, 'import'])->name('requests.import');
    Route::resource('monitoring/requests', ServiceRequestController::class)->names('requests')->parameters(['requests' => 'service_request']);
    Route::get('monitoring/petitions-export', [PetitionController::class, 'export'])->name('petitions.export');
    Route::post('monitoring/petitions-import-preview', [PetitionController::class, 'importPreview'])->name('petitions.import-preview');
    Route::post('monitoring/petitions-import', [PetitionController::class, 'import'])->name('petitions.import');
    Route::resource('monitoring/petitions', PetitionController::class)->names('petitions');
    Route::get('monitoring/asset-receptions-export', [AssetReceptionController::class, 'export'])->name('asset-receptions.export');
    Route::post('monitoring/asset-receptions-import-preview', [AssetReceptionController::class, 'importPreview'])->name('asset-receptions.import-preview');
    Route::post('monitoring/asset-receptions-import', [AssetReceptionController::class, 'import'])->name('asset-receptions.import');
    Route::resource('monitoring/asset-receptions', AssetReceptionController::class)->names('asset-receptions');
    Route::get('monitoring/asset-check', [AssetCheckController::class, 'index'])->name('asset-check.index');
    Route::resource('monitoring/external-checkins', ExternalCheckinController::class)->names('external-checkins')->except(['create', 'store', 'show']);
    Route::get('monitoring/external-checkins-feed', [ExternalCheckinController::class, 'feed'])->name('external-checkins.feed');
    Route::post('monitoring/external-checkins/bulk-delete', [ExternalCheckinController::class, 'bulkDestroy'])->name('external-checkins.bulk-destroy');
    Route::get('monitoring/online-classes', [OnlineClassController::class, 'index'])->name('online-classes.index');
    Route::get('monitoring/online-classes-feed', [OnlineClassController::class, 'feed'])->name('online-classes.feed');
    Route::put('monitoring/online-classes/{online_checkin}', [OnlineClassController::class, 'update'])->name('online-classes.update');
    Route::delete('monitoring/online-classes/{online_checkin}', [OnlineClassController::class, 'destroy'])->name('online-classes.destroy');
    Route::post('monitoring/online-classes/bulk-delete', [OnlineClassController::class, 'bulkDestroy'])->name('online-classes.bulk-destroy');
    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::post('monitoring/document-records/extract', [DocumentRecordController::class, 'extract'])->name('document-records.extract');
    Route::resource('monitoring/document-records', DocumentRecordController::class)->names('document-records')->except(['show']);
    Route::get('monitoring/document-records-export', [DocumentRecordController::class, 'export'])->name('document-records.export');
    Route::post('monitoring/document-records-import-preview', [DocumentRecordController::class, 'importPreview'])->name('document-records.import-preview');
    Route::post('monitoring/document-records-import', [DocumentRecordController::class, 'import'])->name('document-records.import');

    Route::resource('monitoring/incident-records', IncidentRecordController::class)->names('incident-records');
    Route::get('monitoring/incident-records/{incident_record}/export', [IncidentRecordController::class, 'export'])->name('incident-records.export');
    Route::get('monitoring/document-lookup', [DocumentLookupController::class, 'index'])->name('document-lookup.index');
    Route::get('monitoring/document-lookup/search', [DocumentLookupController::class, 'search'])->name('document-lookup.search');
    Route::get('monitoring/document-lookup/{document_record}', [DocumentLookupController::class, 'show'])->name('document-lookup.show');
    Route::post('monitoring/document-lookup/{document_record}/verify', [DocumentLookupController::class, 'verifyPassword'])->name('document-lookup.verify');
    Route::get('monitoring/online-checkins', [MonitoringOverviewController::class, 'onlineCheckins'])->name('online-checkins.index');

    Route::get('monitoring/online', [MonitoringScheduleController::class, 'online'])->name('monitoring.online.index');
    Route::get('monitoring/in-person', [MonitoringScheduleController::class, 'inPerson'])->name('monitoring.in-person.index');
    Route::get('monitoring/exams', [MonitoringScheduleController::class, 'exams'])->name('monitoring.exams.index');
    Route::get('monitoring/external-practice', [MonitoringScheduleController::class, 'externalPractice'])->name('monitoring.external-practice.index');
    Route::get('monitoring/homeroom', [MonitoringScheduleController::class, 'homeroom'])->name('monitoring.homeroom.index');
    Route::get('monitoring/{module}/data', [MonitoringScheduleController::class, 'data'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.data');
    Route::get('monitoring/{module}/filter-presets', [MonitoringScheduleController::class, 'filterPresets'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.filter-presets');
    Route::put('monitoring/{module}/filter-presets', [MonitoringScheduleController::class, 'saveFilterPresets'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.filter-presets.save');
    Route::get('monitoring/{module}/export', [MonitoringScheduleController::class, 'export'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.export');
    Route::post('monitoring/{module}/import', [MonitoringScheduleController::class, 'import'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.import');
    Route::post('monitoring/{module}/extract-incident-detail', [MonitoringScheduleController::class, 'extractIncidentDetail'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.extract-incident-detail');
    Route::post('monitoring/{module}/{schedule}/clear-recording', [MonitoringScheduleController::class, 'clearRecording'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.clear-recording');
    Route::get('monitoring/{module}/{schedule}', [MonitoringScheduleController::class, 'show'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.show');
    Route::get('monitoring/{module}/{schedule}/edit', [MonitoringScheduleController::class, 'edit'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.edit');
    Route::put('monitoring/{module}/{schedule}', [MonitoringScheduleController::class, 'update'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.update');
    Route::post('monitoring/{module}', [MonitoringScheduleController::class, 'store'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.store');
    Route::delete('monitoring/{module}/{schedule}', [MonitoringScheduleController::class, 'destroy'])
        ->whereIn('module', ['online', 'in-person', 'exams', 'external-practice', 'homeroom'])
        ->name('monitoring.schedules.destroy');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/security', [SettingsController::class, 'security'])->name('settings.security');
    Route::delete('settings/security/sessions/others', [SettingsController::class, 'destroyOtherSessions'])->name('settings.security.sessions.others');
    Route::delete('settings/security/sessions/{sessionId}', [SettingsController::class, 'destroySession'])->name('settings.security.sessions.destroy');

    Route::get('settings/schedules-import-template', [ScheduleController::class, 'importTemplate'])->name('schedules.import-template');
    Route::get('settings/schedules-data', [ScheduleController::class, 'data'])->name('schedules.data');
    Route::get('settings/schedules-filter-presets', [ScheduleController::class, 'filterPresets'])->name('schedules.filter-presets');
    Route::put('settings/schedules-filter-presets', [ScheduleController::class, 'saveFilterPresets'])->name('schedules.filter-presets.save');
    Route::post('settings/schedules-bulk-delete', [ScheduleController::class, 'bulkDestroy'])->name('schedules.bulk-destroy');
    Route::delete('settings/schedules-by-date', [ScheduleController::class, 'destroyByDate'])->name('schedules.destroy-by-date');
    Route::post('settings/schedules-import-preview', [ScheduleController::class, 'importPreview'])->name('schedules.import-preview');
    Route::post('settings/schedules-import-batch', [ScheduleController::class, 'importBatch'])->name('schedules.import-batch');
    Route::resource('settings/schedules', ScheduleController::class)->names('schedules')->except(['show']);
    Route::get('settings/schedules-export', [ScheduleController::class, 'export'])->name('schedules.export');
    Route::post('settings/schedules-import', [ScheduleController::class, 'import'])->name('schedules.import');
    Route::get('settings/parameters', [SystemParameterController::class, 'index'])->name('parameters.index');
    Route::post('settings/parameters/bulk', [SystemParameterController::class, 'bulkUpdate'])->name('parameters.bulk');
    Route::post('settings/parameters/verify/google-sheet', [SystemParameterController::class, 'verifyGoogleSheet'])->name('parameters.verify.google-sheet');
    Route::post('settings/parameters/verify/summary-google-sheet', [SystemParameterController::class, 'verifySummaryGoogleSheet'])->name('parameters.verify.summary-google-sheet');
    Route::post('settings/parameters/verify/evidence', [SystemParameterController::class, 'verifyEvidence'])->name('parameters.verify.evidence');
    Route::post('settings/parameters/verify/ai', [SystemParameterController::class, 'verifyAi'])->name('parameters.verify.ai');
    Route::post('settings/parameters/verify/email', [SystemParameterController::class, 'verifyEmail'])->name('parameters.verify.email');
    Route::post('settings/parameters/verify/lecturer-portal', [SystemParameterController::class, 'verifyLecturerPortal'])->name('parameters.verify.lecturer-portal');

    Route::get('monitoring/evidence', [EvidenceController::class, 'index'])->name('monitoring.evidence.index');
    Route::get('monitoring/evidence/data', [EvidenceController::class, 'data'])->name('monitoring.evidence.data');
    Route::delete('monitoring/evidence/{evidenceId}', [EvidenceController::class, 'destroy'])->name('monitoring.evidence.destroy');
    Route::post('monitoring/evidence/upload', [EvidenceController::class, 'upload'])->name('monitoring.evidence.upload');
    Route::get('ai/assistant', [AiAssistantController::class, 'index'])->name('ai.assistant');
    Route::post('ai/assistant/ask', [AiAssistantController::class, 'ask'])->name('ai.assistant.ask');
    Route::post('ai/extract/asset-reception', [AiAssistantController::class, 'extractAssetReception'])->name('ai.extract.asset-reception');
    Route::post('ai/extract/petition', [AiAssistantController::class, 'extractPetition'])->name('ai.extract.petition');
    Route::post('ai/extract/service-request', [AiAssistantController::class, 'extractServiceRequest'])->name('ai.extract.service-request');
    Route::get('tools/api-documentation', [ApiDocumentationController::class, 'index'])->name('api-documentation.index');
    Route::post('tools/ckeditor/upload', [CkeditorUploadController::class, 'upload'])->name('ckeditor.upload');
    Route::get('tools/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('tools/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('tools/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('tools/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::post('discussion', [DiscussionController::class, 'storeSection'])->name('discussion.store');

    Route::get('messaging', [MessagingController::class, 'index'])->name('messaging.index');
    Route::get('messaging/notifications', [MessagingController::class, 'notifications'])->name('messaging.notifications');
    Route::post('messaging/mark-all-read', [MessagingController::class, 'markAllRead'])->name('messaging.mark-all-read');
    Route::post('messaging', [MessagingController::class, 'store'])->name('messaging.store');
    Route::get('messaging/{message}/read', [MessagingController::class, 'read'])->name('messaging.read');
    Route::post('messaging/{message}/trash', [MessagingController::class, 'trash'])->name('messaging.trash');
    Route::post('messaging/{message}/restore', [MessagingController::class, 'restore'])->name('messaging.restore');
    Route::delete('messaging/{message}', [MessagingController::class, 'destroy'])->name('messaging.destroy');

    Route::get('settings/permissions', [PermissionSettingsController::class, 'index'])->name('permissions.index');
    Route::post('settings/permissions', [PermissionSettingsController::class, 'store'])->name('permissions.store');
    Route::get('settings/permissions/{role}/defaults', [PermissionSettingsController::class, 'defaults'])->name('permissions.defaults');
    Route::get('settings/permissions/{role}/edit', [PermissionSettingsController::class, 'edit'])->name('permissions.edit');
    Route::put('settings/permissions/{role}', [PermissionSettingsController::class, 'update'])->name('permissions.update');
    Route::delete('settings/permissions/{role}', [PermissionSettingsController::class, 'destroy'])->name('permissions.destroy');

    Route::get('settings/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('settings/activity-logs-statistics', [ActivityLogController::class, 'statistics'])->name('activity-logs.statistics');
    Route::get('settings/activity-logs-export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::post('settings/activity-logs/bulk-delete', [ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk-destroy');
    Route::delete('settings/activity-logs/{activityLog}', [ActivityLogController::class, 'destroy'])->name('activity-logs.destroy');
    Route::get('settings/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('settings/backup/purge-data', [BackupController::class, 'purgeData'])->name('backup.purge-data');
    Route::post('settings/backup/export', [BackupController::class, 'export'])->name('backup.export');
    Route::post('settings/backup/import', [BackupController::class, 'import'])->name('backup.import');
    Route::post('settings/backup/{filename}/restore', [BackupController::class, 'restore'])
        ->where('filename', '.*')
        ->name('backup.restore');
    Route::put('settings/backup/{filename}/rename', [BackupController::class, 'rename'])
        ->where('filename', '.*')
        ->name('backup.rename');
    Route::delete('settings/backup/{filename}/delete', [BackupController::class, 'destroy'])
        ->where('filename', '.*')
        ->name('backup.destroy');
    Route::get('settings/backup/download/{filename}', [BackupController::class, 'download'])
        ->where('filename', '.*')
        ->name('backup.download');

    Route::get('settings/project-files', [ProjectFileController::class, 'index'])->name('project-files.index');
    Route::get('settings/project-files/list', [ProjectFileController::class, 'list'])->name('project-files.list');
    Route::get('settings/project-files/show', [ProjectFileController::class, 'show'])->name('project-files.show');
    Route::get('settings/project-files/download', [ProjectFileController::class, 'download'])->name('project-files.download');
    Route::post('settings/project-files/batch-download', [ProjectFileController::class, 'batchDownload'])->name('project-files.batch-download');
    Route::post('settings/project-files', [ProjectFileController::class, 'store'])->name('project-files.store');
    Route::post('settings/project-files/batch-upload', [ProjectFileController::class, 'batchUpload'])->name('project-files.batch-upload');
    Route::post('settings/project-files/compress', [ProjectFileController::class, 'compress'])->name('project-files.compress');
    Route::put('settings/project-files/move', [ProjectFileController::class, 'move'])->name('project-files.move');
    Route::post('settings/project-files/extract', [ProjectFileController::class, 'extract'])->name('project-files.extract');
    Route::put('settings/project-files/content', [ProjectFileController::class, 'update'])->name('project-files.update');
    Route::put('settings/project-files/rename', [ProjectFileController::class, 'rename'])->name('project-files.rename');
    Route::delete('settings/project-files', [ProjectFileController::class, 'destroy'])->name('project-files.destroy');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('daily/export', [ReportController::class, 'dailyExport'])->name('daily.export');
        Route::get('daily/google-sheets/tabs', [ReportController::class, 'dailyGoogleSheetTabs'])->name('daily.google-sheets.tabs');
        Route::post('daily/google-sheets/push', [ReportController::class, 'dailyGoogleSheetPush'])->name('daily.google-sheets.push');
        Route::get('comprehensive', [ReportController::class, 'comprehensive'])->name('comprehensive');
        Route::get('student-violations', [ReportController::class, 'studentViolations'])->name('student-violations');
        Route::get('good-deeds', [ReportController::class, 'goodDeeds'])->name('good-deeds');
        Route::get('request-reports', [ReportController::class, 'requestReports'])->name('request-reports');
        Route::get('incident-records-reports', [ReportController::class, 'incidentRecordsReports'])->name('incident-records-reports');
        Route::get('incident-reports', [ReportController::class, 'incidentReports'])->name('incident-reports');
        Route::get('interactive-data/{variant}', [ReportController::class, 'interactiveData'])->name('interactive-data');
        Route::get('comprehensive/export', [ReportController::class, 'exportComprehensive'])->name('comprehensive.export');
        Route::get('comprehensive/monthly-report', [ReportController::class, 'exportComprehensiveMonthlyReport'])->name('comprehensive.monthly-report');
        Route::get('student-violations/export', [ReportController::class, 'exportStudentViolations'])->name('student-violations.export');
        Route::get('good-deeds/export', [ReportController::class, 'exportGoodDeeds'])->name('good-deeds.export');
        Route::get('request-reports/export', [ReportController::class, 'exportRequestReports'])->name('request-reports.export');
        Route::get('incident-records-reports/export', [ReportController::class, 'exportIncidentRecordsReports'])->name('incident-records-reports.export');
        Route::get('incident-reports/export', [ReportController::class, 'exportIncidentReports'])->name('incident-reports.export');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('storage/evidence/{path}', [PublicStorageController::class, 'evidence'])
        ->where('path', '.*')
        ->name('storage.evidence');
});

require __DIR__.'/auth.php';
