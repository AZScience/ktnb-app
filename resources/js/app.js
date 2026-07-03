import Alpine from 'alpinejs';
import { registerFloatingMenu } from './floating-menu.js';
import { registerNttuMultiSelectPanel } from './nttu-multi-select.js';
import { registerAvatarInput } from './avatar-input.js';
import { registerNttuShell } from './shell.js';
import { registerHeader } from './header.js';
import { registerLanguage } from './language.js';
import { registerNttuColumnResize } from './nttu-column-resize.js';

window.Alpine = Alpine;

registerNttuColumnResize();
registerFloatingMenu(Alpine);
registerNttuMultiSelectPanel(Alpine);
registerAvatarInput(Alpine);
registerNttuShell(Alpine);
registerHeader(Alpine);
registerLanguage(Alpine);

function hasXData(fragment) {
    return document.querySelector(`[x-data*="${fragment}"]`);
}

async function loadPageModules() {
    const loaders = [];

    if (hasXData('catalogTablePage')) {
        loaders.push(import('./catalog-table.js').then(({ catalogTablePage }) => {
            window.catalogTablePage = catalogTablePage;
            Alpine.data('catalogTablePage', catalogTablePage);
        }));
    }
    if (hasXData('interactiveReportPage')) {
        loaders.push(import('./interactive-report.js').then(({ registerInteractiveReport }) => {
            registerInteractiveReport(Alpine);
        }));
    }
    if (hasXData('dailyReportPage')) {
        loaders.push(import('./daily-report.js').then(({ registerDailyReport }) => {
            registerDailyReport(Alpine);
        }));
    }
    if (hasXData('dashboardScheduleLookup') || hasXData('shiftScheduleWidget') || hasXData('periodScheduleWidget')) {
        loaders.push(import('./dashboard.js').then(({ registerShiftScheduleWidget, dashboardScheduleLookup }) => {
            Alpine.data('dashboardScheduleLookup', dashboardScheduleLookup);
            registerShiftScheduleWidget(Alpine);
        }));
    }
    if (document.getElementById('system-overview-charts')) {
        loaders.push(import('./system-overview-charts.js'));
    }
    if (hasXData('monitoringSchedulesPage')) {
        loaders.push(import('./monitoring-schedules.js'));
    }
    if (hasXData('dailyScheduleSettingsPage')) {
        loaders.push(import('./daily-schedule-settings.js').then(({ registerDailyScheduleSettings }) => {
            registerDailyScheduleSettings(Alpine);
        }));
    }
    if (hasXData('checkinMonitorPage')) {
        loaders.push(import('./checkin-monitor.js').then(({ registerCheckinMonitor }) => {
            registerCheckinMonitor(Alpine);
        }));
    }
    if (hasXData('permissionSettingsPage')) {
        loaders.push(import('./permission-settings.js').then(({ registerPermissionSettings }) => {
            registerPermissionSettings(Alpine);
        }));
    }
    if (hasXData('activityLogSettingsPage')) {
        loaders.push(import('./activity-log-settings.js').then(({ registerActivityLogSettings }) => {
            registerActivityLogSettings(Alpine);
        }));
    }
    if (hasXData('backupSettingsPage')) {
        loaders.push(import('./backup-settings.js').then(({ registerBackupSettings }) => {
            registerBackupSettings(Alpine);
        }));
    }
    if (hasXData('projectFilesPage')) {
        loaders.push(import('./project-files.js').then(({ registerProjectFiles }) => {
            registerProjectFiles(Alpine);
        }));
    }
    if (hasXData('systemParametersPage')) {
        loaders.push(import('./system-parameters.js').then(({ registerSystemParameters }) => {
            registerSystemParameters(Alpine);
        }));
    }
    if (hasXData('documentLookupPage')) {
        loaders.push(import('./document-lookup.js').then(({ registerDocumentLookup }) => {
            registerDocumentLookup(Alpine);
        }));
    }
    if (hasXData('messagingPage')) {
        loaders.push(import('./messaging.js').then(({ registerMessaging }) => {
            registerMessaging(Alpine);
        }));
    }
    if (hasXData('discussionBoardPage') || hasXData('discussionCollaborationBoard')) {
        loaders.push(import('./discussion-board.js').then(({ registerDiscussionBoard }) => {
            registerDiscussionBoard(Alpine);
        }));
    }
    if (hasXData('lecturerPortalPage')) {
        loaders.push(import('./lecturer-portal.js').then(({ registerLecturerPortal }) => {
            registerLecturerPortal(Alpine);
        }));
    }
    if (hasXData('feedbackFormPage')) {
        loaders.push(import('./feedback-form.js').then(({ registerFeedbackForm }) => {
            registerFeedbackForm(Alpine);
        }));
    }
    if (hasXData('evidenceManager')) {
        loaders.push(import('./evidence-manager.js').then(({ registerEvidenceManager }) => {
            registerEvidenceManager(Alpine);
        }));
    }
    if (hasXData('settingsPage')) {
        loaders.push(import('./settings-page.js').then(({ registerSettingsPage }) => {
            registerSettingsPage(Alpine);
        }));
    }
    if (hasXData('profilePage')) {
        loaders.push(import('./profile-page.js').then(({ registerProfilePage }) => {
            registerProfilePage(Alpine);
        }));
    }
    if (hasXData('loginPage')) {
        loaders.push(import('./login.js').then(({ registerLoginPage }) => {
            registerLoginPage(Alpine);
        }));
    }
    if (hasXData('aiAssistantPage')) {
        loaders.push(import('./ai-assistant.js').then(({ registerAiAssistant }) => {
            registerAiAssistant(Alpine);
        }));
    }

    await Promise.all(loaders);
}

loadPageModules().then(() => {
    Alpine.start();
});
