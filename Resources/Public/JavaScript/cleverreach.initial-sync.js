(function() {
    document.addEventListener("DOMContentLoaded", function(event) {
    
        function initialSyncCompleteHandler() {
            location.reload();
        }
    
        CleverReach.StatusChecker.init({
            statusCheckUrl: document.getElementById('cr-admin-status-check-url').value  + '&form_key=' + window.FORM_KEY,
            baseSelector: '.cr-container',
            finishedStatus: 'completed',
            onComplete: initialSyncCompleteHandler,
            pendingStatusClasses: ['cr-icofont-wait'],
            inProgressStatusClasses: ['cr-icofont-loader'],
            doneStatusClasses: ['cr-icofont-check']
        });
    });
})();