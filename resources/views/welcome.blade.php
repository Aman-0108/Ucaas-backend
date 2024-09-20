<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <h1>Test {{ config('globals.support_mail') }}</h1>

    <script>

        const token = '34|LCf5fJO66oSvOPUxY9pwFYE34cI02USDtZ2VTKD0';

        const ws = new WebSocket('wss://192.168.1.88:8093');

        ws.onopen = function() {
            console.log('WebSocket connection established.');
        };

        ws.onmessage = function(event) {
            console.log('WebSocket message received:', event.data);
        };

        ws.onclose = function() {
            console.log('WebSocket connection closed.');
        };

        ws.onerror = function(error) {
            console.error('WebSocket error:', error);
        };
    </script>

    <!-- <script type="module">
            // Import the functions you need from the SDKs you need
            import {initializeApp } from "https://www.gstatic.com/firebasejs/10.12.1/firebase-app.js";
            // import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.12.1/firebase-analytics.js";
            import { getMessaging } from "https://www.gstatic.com/firebasejs/10.12.1/firebase-messaging.js"
           
            // Your web app's Firebase configuration
            // For Firebase JS SDK v7.20.0 and later, measurementId is optional
            const firebaseConfig = {
                apiKey: "AIzaSyCk8obCmvMf3SSC9rkAoF9wJi5dzHNDjUw",
                authDomain: "ucaas-38697.firebaseapp.com",
                projectId: "ucaas-38697",
                storageBucket: "ucaas-38697.appspot.com",
                messagingSenderId: "145588882515",
                appId: "1:145588882515:web:2f784d11a1d3c936e4caa6",
                measurementId: "G-GH6J95RH2J"
            };

            // Initialize Firebase
            const app = initializeApp(firebaseConfig);
            const messaging = getMessaging(app);
            // const analytics = getAnalytics(app);

            navigator.serviceWorker.register("sw.js").then(registration => {
                console.log(registration,'dfgrt')
                getToken(messaging, {
                    serviceWorkerRegistration: registration,
                    vapidKey: 'BE6XpLzrY9HtamZPKs48I2Cc2EbrdAKiUBKUIEHuqm_mYEWRdXaVgBKibTlT8M-fFFHPZnYDUa-mxJWyGyDg5RM'
                }).then((currentToken) => {
                    if (currentToken) {
                        console.log("Token is: " + currentToken);
                        // Send the token to your server and update the UI if necessary
                        // ...
                    } else {
                        // Show permission request UI
                        console.log('No registration token available. Request permission to generate one.');
                        // ...
                    }
                }).catch((err) => {
                    console.log('An error occurred while retrieving token. ', err);
                    // ...
                });
            });
        </script> -->
</body>

</html>