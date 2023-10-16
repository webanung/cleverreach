(function() {
    document.addEventListener("DOMContentLoaded", function() {
        let authUrl = document.getElementById('cr-auth-url').value,
            checkStatusUrl = document.getElementById('cr-check-status-url').value,
            loginButton = document.getElementById('cr-log-account'),
            createAccountButton = document.getElementById('cr-new-account');

        loginButton.addEventListener('click', function () {
            startAuthProcess(authUrl + '#login');
        });

        createAccountButton.addEventListener('click', function () {
            startAuthProcess(authUrl + '#register');
        });

        let auth = new CleverReach.Authorization(authUrl, checkStatusUrl);
        showSpinner();
        auth.getStatus(function() {
            hideSpinner();
        });

        function startAuthProcess(authUrl) {
            showSpinner();
            let auth = new CleverReach.Authorization(authUrl, checkStatusUrl);
            auth.checkConnectionStatus(function () {
                location.reload();
            });
        }

        function showSpinner() {
            document.getElementsByClassName('cr-loader-big')[0].style.display = 'flex';
            document.getElementsByClassName('cr-connecting')[0].style.display = 'block';
            document.getElementsByClassName('cr-content-window-wrapper')[0].style.display = 'none';
        }

        function hideSpinner() {
            document.getElementsByClassName('cr-loader-big')[0].style.display = 'none';
            document.getElementsByClassName('cr-connecting')[0].style.display = 'none';
            document.getElementsByClassName('cr-content-window-wrapper')[0].style.display = '';
        }
    });
})();
