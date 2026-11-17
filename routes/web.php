<?php

use App\Http\Controllers\Admin\BranchManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CompanySystemSyncController;
use App\Http\Controllers\Case\CaseAssigneeController;
use App\Http\Controllers\Case\CaseCommentController;
use App\Http\Controllers\Case\CaseController;
use App\Http\Controllers\Case\CaseSettingController;
use App\Http\Controllers\Case\CaseTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditRequest\EditRequestController;
use App\Http\Controllers\EditRequest\EditRequestReportController;
use App\Http\Controllers\Hr\HrDashboardController;
use App\Http\Controllers\Hr\HrInternshipController;
use App\Http\Controllers\Hr\HrQualificationController;
use App\Http\Controllers\Hr\HrRecruitmentController;
use App\Http\Controllers\Hr\HrSettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\ItSurveyController;
use App\Http\Controllers\Sales\SalesCaseController;
use App\Http\Controllers\TelecomDashboardController;
use App\Http\Controllers\TelecomPhoneNumberController;
use App\Http\Controllers\TelecomBroadbandController;
use App\Http\Controllers\TelecomProviderController;
use App\Http\Controllers\KaldisCommunicationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store']);

// Public Customer Survey Routes (Mobile QR code access)
Route::get('survey', [SurveyController::class, 'publicForm'])->name('survey.public');
Route::post('survey/submit', [SurveyController::class, 'submit'])->name('survey.submit');
Route::get('survey/mini-app', [SurveyController::class, 'publicForm'])->name('survey.mini-app');

// ============================================================
// TELEGRAM MINI APP - IT BRANCH SURVEY & AUDIT (In-app Telegram access)
// ============================================================
Route::get('it-surveys/mini-app', [ItSurveyController::class, 'miniApp'])->name('it-surveys.mini-app');
Route::post('it-surveys/mini-app', [ItSurveyController::class, 'miniAppStore'])->name('it-surveys.mini-app.store');
Route::get('it-surveys/app', [ItSurveyController::class, 'miniApp'])->name('it-surveys.app');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('password/update', [PasswordController::class, 'update'])->name('password.update');
    Route::post('password/dismiss', [PasswordController::class, 'dismiss'])->name('password.dismiss');

    // Manual Company System Synchronization (Super Admin only)
    Route::post('company-system/sync', [CompanySystemSyncController::class, 'sync'])->name('company-system.sync');

    // System Settings & Telegram Configuration
    Route::get('settings', [CaseSettingController::class, 'index'])->name('settings.index')->middleware('can:case.settings.manage');
    Route::post('settings', [CaseSettingController::class, 'update'])->name('settings.update')->middleware('can:case.settings.manage');
    Route::post('settings/test-telegram', [CaseSettingController::class, 'testTelegram'])->name('settings.test-telegram')->middleware('can:case.settings.manage');

    // System Administration - Branches Management
    Route::prefix('admin')->name('admin.')->middleware('can:view users|case.settings.manage|hr.manage')->group(function () {
        Route::get('branches', [BranchManagementController::class, 'index'])->name('branches.index');
        Route::post('branches', [BranchManagementController::class, 'store'])->name('branches.store');
        Route::put('branches/{id}', [BranchManagementController::class, 'update'])->name('branches.update');
        Route::delete('branches/{id}', [BranchManagementController::class, 'destroy'])->name('branches.destroy');
        Route::post('branches/sync', [BranchManagementController::class, 'sync'])->name('branches.sync');
    });

    // ============================================================
    // CASE MANAGEMENT MODULE
    // ============================================================
    Route::prefix('cases')->name('cases.')->middleware('can:case.view')->group(function () {
        Route::get('/', [CaseController::class, 'index'])->name('index');
        Route::get('/create', [CaseController::class, 'create'])->name('create');
        Route::post('/', [CaseController::class, 'store'])->name('store');
        Route::get('/sla', [CaseController::class, 'sla'])->name('sla');
        Route::post('/export', [CaseController::class, 'export'])->name('export');

        Route::post('/assignees', [CaseAssigneeController::class, 'store'])->name('assignees.store');
        Route::delete('/assignees/{id}', [CaseAssigneeController::class, 'destroy'])->name('assignees.destroy');

        Route::post('/types', [CaseTypeController::class, 'store'])->name('types.store');
        Route::delete('/types/{id}', [CaseTypeController::class, 'destroy'])->name('types.destroy');

        Route::redirect('/settings', '/settings');

        Route::get('/{id}', [CaseController::class, 'show'])->name('show')->whereNumber('id');
        Route::put('/{id}', [CaseController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/{id}', [CaseController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::post('/{id}/comments', [CaseCommentController::class, 'store'])->name('comments.store')->whereNumber('id');
    });

    // ============================================================
    // EDIT & VOID REQUESTS MODULE
    // ============================================================
    Route::prefix('edit-requests')->name('edit-requests.')->group(function () {
        Route::get('/', [EditRequestController::class, 'index'])->name('index');
        Route::get('/create', [EditRequestController::class, 'create'])->name('create');
        Route::post('/', [EditRequestController::class, 'store'])->name('store');
        Route::get('/reports', [EditRequestReportController::class, 'index'])->name('reports')->middleware('can:edit_request.reports.view');
        Route::get('/reports/export-csv', [EditRequestReportController::class, 'exportCsv'])->name('reports.export-csv')->middleware('can:edit_request.reports.view');
        Route::post('/reports/telegram', [EditRequestReportController::class, 'sendTelegramReport'])->name('reports.telegram')->middleware('can:edit_request.reports.view');
        Route::post('/bulk-status', [EditRequestController::class, 'bulkUpdateStatus'])->name('bulk-status');

        Route::get('/{id}', [EditRequestController::class, 'show'])->name('show');
        Route::put('/{id}/status', [EditRequestController::class, 'updateStatus'])->name('status');
        Route::put('/{id}/mark-done', [EditRequestController::class, 'markDone'])->name('mark-done');
        Route::delete('/{id}', [EditRequestController::class, 'destroy'])->name('destroy');
    });

    Route::redirect('surveys', 'it-surveys');

    // ============================================================
    // IT BRANCH INFRASTRUCTURE & HARDWARE AUDIT MODULE
    // ============================================================
    Route::prefix('it-surveys')->name('it-surveys.')->middleware('can:it_survey.view')->group(function () {
        Route::get('/', [ItSurveyController::class, 'index'])->name('index');
        Route::get('/create', [ItSurveyController::class, 'create'])->name('create')->middleware('can:it_survey.create');
        Route::post('/', [ItSurveyController::class, 'store'])->name('store')->middleware('can:it_survey.create');
        Route::get('/hardware', [ItSurveyController::class, 'hardwareIndex'])->name('hardware');
        Route::post('/hardware', [ItSurveyController::class, 'hardwareStore'])->name('hardware.store')->middleware('can:it_survey.manage');
        Route::delete('/hardware/{id}', [ItSurveyController::class, 'hardwareDestroy'])->name('hardware.destroy')->middleware('can:it_survey.manage');
        Route::get('/export/reports', [ItSurveyController::class, 'exportReports'])->name('export.reports');
        Route::get('/export/hardware', [ItSurveyController::class, 'exportHardware'])->name('export.hardware');
        Route::get('/{id}', [ItSurveyController::class, 'show'])->name('show');
        Route::delete('/{id}', [ItSurveyController::class, 'destroy'])->name('destroy')->middleware('can:it_survey.manage');
    });

    // ============================================================
    // SALES DISCREPANCY & Z-REPORT RECONCILIATION MODULE
    // ============================================================
    Route::prefix('sales')->name('sales.')->middleware('can:sales_case.view')->group(function () {
        Route::get('/dashboard', [SalesCaseController::class, 'dashboard'])->name('dashboard');
        Route::get('/cases', [SalesCaseController::class, 'index'])->name('index');
        Route::get('/create', [SalesCaseController::class, 'create'])->name('create')->middleware('can:sales_case.create');
        Route::post('/cases', [SalesCaseController::class, 'store'])->name('store')->middleware('can:sales_case.create');
        Route::get('/export', [SalesCaseController::class, 'export'])->name('export');
        Route::get('/cases/{id}', [SalesCaseController::class, 'show'])->name('show');
        Route::put('/cases/{id}', [SalesCaseController::class, 'update'])->name('update')->middleware('can:sales_case.manage');
        Route::post('/cases/{id}/progress', [SalesCaseController::class, 'updateProgress'])->name('update-progress');
        Route::post('/cases/{id}/approve', [SalesCaseController::class, 'approve'])->name('approve')->middleware('can:sales_case.manage');
        Route::post('/cases/{id}/comments', [SalesCaseController::class, 'addComment'])->name('comment');
    });

    // ============================================================
    // HR MANAGEMENT MODULE
    // ============================================================
    Route::prefix('hr')->name('hr.')->middleware('can:hr.view')->group(function () {
        Route::get('/', [HrDashboardController::class, 'index'])->name('dashboard');

        // Internship Requests
        Route::get('/internships', [HrInternshipController::class, 'index'])->name('internships.index');
        Route::post('/internships', [HrInternshipController::class, 'store'])->name('internships.store');
        Route::put('/internships/{id}/status', [HrInternshipController::class, 'updateStatus'])->name('internships.status');
        Route::get('/internships/{id}/letter', [HrInternshipController::class, 'letter'])->name('internships.letter');
        Route::delete('/internships/{id}', [HrInternshipController::class, 'destroy'])->name('internships.destroy');

        // Qualification Records
        Route::get('/qualifications', [HrQualificationController::class, 'index'])->name('qualifications.index');
        Route::post('/qualifications', [HrQualificationController::class, 'store'])->name('qualifications.store');
        Route::put('/qualifications/{id}/status', [HrQualificationController::class, 'updateStatus'])->name('qualifications.status');
        Route::get('/qualifications/{id}/letter', [HrQualificationController::class, 'letter'])->name('qualifications.letter');
        Route::delete('/qualifications/{id}', [HrQualificationController::class, 'destroy'])->name('qualifications.destroy');

        // Recruitment Placement
        Route::get('/recruitments', [HrRecruitmentController::class, 'index'])->name('recruitments.index');
        Route::post('/recruitments', [HrRecruitmentController::class, 'store'])->name('recruitments.store');
        Route::get('/recruitments/{id}/letter', [HrRecruitmentController::class, 'letter'])->name('recruitments.letter');
        Route::delete('/recruitments/{id}', [HrRecruitmentController::class, 'destroy'])->name('recruitments.destroy');

        // Settings & Telegram Routing
        Route::get('/settings', [HrSettingController::class, 'index'])->name('settings.index')->middleware('can:hr.manage');
        Route::post('/settings', [HrSettingController::class, 'storeSetting'])->name('settings.store')->middleware('can:hr.manage');
        Route::put('/settings/{id}/chat', [HrSettingController::class, 'updateBranchChat'])->name('settings.update-chat')->middleware('can:hr.manage');
        Route::delete('/settings/{id}', [HrSettingController::class, 'destroySetting'])->name('settings.destroy')->middleware('can:hr.manage');
        Route::post('/settings/bot-token', [HrSettingController::class, 'saveBotToken'])->name('settings.bot-token')->middleware('can:hr.manage');
        Route::post('/settings/test-telegram', [HrSettingController::class, 'testTelegram'])->name('settings.test-telegram')->middleware('can:hr.manage');
    });

    // ============================================================
    // TELECOM MANAGEMENT MODULE
    // ============================================================
    Route::middleware(['permission:view telecom management|telecom.view'])->prefix('telecom')->group(function () {
        Route::get('/dashboard', [TelecomDashboardController::class, 'index'])->name('telecom.dashboard');

        // Phone Numbers / SIM Cards
        Route::get('/phone-numbers/export', [TelecomPhoneNumberController::class, 'export'])
            ->name('telecom.phone-numbers.export')
            ->middleware('can:export telecom data|telecom.export');
        Route::post('/phone-numbers/{phoneNumber}/transfer', [TelecomPhoneNumberController::class, 'transfer'])
            ->name('telecom.phone-numbers.transfer')
            ->middleware('can:manage telecom connections|telecom.phone.transfer');
        Route::resource('phone-numbers', TelecomPhoneNumberController::class)->names([
            'index' => 'telecom.phone-numbers.index',
            'create' => 'telecom.phone-numbers.create',
            'store' => 'telecom.phone-numbers.store',
            'edit' => 'telecom.phone-numbers.edit',
            'update' => 'telecom.phone-numbers.update',
            'destroy' => 'telecom.phone-numbers.destroy',
        ]);

        // Broadband & WTTx Connections
        Route::get('/broadbands/export', [TelecomBroadbandController::class, 'export'])
            ->name('telecom.broadbands.export')
            ->middleware('can:export telecom data|telecom.export');
        Route::post('/broadbands/{broadband}/transfer', [TelecomBroadbandController::class, 'transfer'])
            ->name('telecom.broadbands.transfer')
            ->middleware('can:manage telecom connections|telecom.broadband.transfer');
        Route::resource('broadbands', TelecomBroadbandController::class)->names([
            'index' => 'telecom.broadbands.index',
            'create' => 'telecom.broadbands.create',
            'store' => 'telecom.broadbands.store',
            'edit' => 'telecom.broadbands.edit',
            'update' => 'telecom.broadbands.update',
            'destroy' => 'telecom.broadbands.destroy',
        ]);

        // Service Providers (Ethio Telecom, Safaricom)
        Route::resource('providers', TelecomProviderController::class)->only(['index', 'store', 'update', 'destroy'])->names([
            'index' => 'telecom.providers.index',
            'store' => 'telecom.providers.store',
            'update' => 'telecom.providers.update',
            'destroy' => 'telecom.providers.destroy',
        ]);
    });

    // ============================================================
    // KALDIS BRANCH COMMUNICATION PLATFORM
    // ============================================================
    Route::middleware(['permission:communication.view|view kaldis communication|view telegram config|manage telegram config'])->group(function () {
        Route::get('kaldis-communication', [KaldisCommunicationController::class, 'index'])->name('kaldis-communication.index');
        Route::post('kaldis-communication/config', [KaldisCommunicationController::class, 'updateConfig'])->name('kaldis-communication.update-config');
        Route::post('kaldis-communication/bindings', [KaldisCommunicationController::class, 'storeBinding'])->name('kaldis-communication.store-binding');
        Route::put('kaldis-communication/bindings', [KaldisCommunicationController::class, 'updateBinding'])->name('kaldis-communication.update-binding');
        Route::delete('kaldis-communication/bindings', [KaldisCommunicationController::class, 'deleteBinding'])->name('kaldis-communication.delete-binding');
        Route::post('kaldis-communication/users', [KaldisCommunicationController::class, 'storeUser'])->name('kaldis-communication.store-user');
        Route::put('kaldis-communication/users', [KaldisCommunicationController::class, 'updateUser'])->name('kaldis-communication.update-user');
        Route::delete('kaldis-communication/users/{telegramUserId}', [KaldisCommunicationController::class, 'deleteUser'])->name('kaldis-communication.delete-user');
        Route::post('kaldis-communication/sync-members', [KaldisCommunicationController::class, 'syncMembersFromTelegram'])->name('kaldis-communication.sync-members');
        Route::post('kaldis-communication/standardize-names', [KaldisCommunicationController::class, 'standardizeRosterNames'])->name('kaldis-communication.standardize-names');
        Route::post('kaldis-communication/fetch-member', [KaldisCommunicationController::class, 'fetchTelegramMember'])->name('kaldis-communication.fetch-member');
        Route::put('kaldis-communication/status/{referenceNo}', [KaldisCommunicationController::class, 'updateStatus'])->name('kaldis-communication.update-status');
        Route::post('kaldis-communication/sync-topics', [KaldisCommunicationController::class, 'syncTopics'])->name('kaldis-communication.sync-topics');
        Route::post('kaldis-communication/bulk-delete-bindings', [KaldisCommunicationController::class, 'bulkDeleteBindings'])->name('kaldis-communication.bulk-delete-bindings');
        Route::post('kaldis-communication/moderation/settings', [KaldisCommunicationController::class, 'updateModerationSettings'])->name('kaldis-communication.moderation.settings');
        Route::post('kaldis-communication/moderation/member', [KaldisCommunicationController::class, 'moderateMember'])->name('kaldis-communication.moderation.member');
        Route::post('kaldis-communication/moderation/broadcast', [KaldisCommunicationController::class, 'broadcastChannel'])->name('kaldis-communication.moderation.broadcast');

        Route::post('kaldis-communication/set-webhook', [KaldisCommunicationController::class, 'registerWebhook'])->name('kaldis-communication.set-webhook');
        Route::post('kaldis-communication/generate-invite-link', [KaldisCommunicationController::class, 'generateInviteLink'])->name('kaldis-communication.generate-invite-link');
        Route::post('kaldis-communication/purge-unlisted', [KaldisCommunicationController::class, 'purgeUnlistedBindings'])->name('kaldis-communication.purge-unlisted');
        Route::post('kaldis-communication/standard-topics', [KaldisCommunicationController::class, 'storeStandardTopicPreset'])->name('kaldis-communication.store-standard-topic');
        Route::post('kaldis-communication/standard-topics/update', [KaldisCommunicationController::class, 'updateStandardTopicPreset'])->name('kaldis-communication.update-standard-topic');
        Route::put('kaldis-communication/standard-topics', [KaldisCommunicationController::class, 'updateStandardTopicPreset']);
        Route::post('kaldis-communication/standard-topics/delete', [KaldisCommunicationController::class, 'deleteStandardTopicPreset'])->name('kaldis-communication.delete-standard-topic');
        Route::delete('kaldis-communication/standard-topics', [KaldisCommunicationController::class, 'deleteStandardTopicPreset']);
        Route::get('kaldis-communication/standard-topics', fn() => redirect()->route('kaldis-communication.index'));
        Route::get('kaldis-communication/standard-topics/update', fn() => redirect()->route('kaldis-communication.index'));
        Route::get('kaldis-communication/standard-topics/delete', fn() => redirect()->route('kaldis-communication.index'));
    });

    // ============================================================
    // USERS, ROLES & PERMISSIONS MANAGEMENT
    // ============================================================
    Route::post('users/sync-all-users', [UserController::class, 'syncAllUsers'])->name('users.sync-all-users');
    Route::resource('users', UserController::class);
    Route::post('users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    Route::resource('roles', RoleController::class);
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
});
