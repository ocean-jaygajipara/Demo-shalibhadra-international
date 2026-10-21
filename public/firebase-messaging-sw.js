importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyAFX-YIhZ9Ny235KkGEXerE-njXngzZ9uU",
    authDomain: "ocean-dbc54.firebaseapp.com",
    projectId: "ocean-dbc54",
    messagingSenderId: "85798723141",
    appId: "1:85798723141:web:818447123fb6e7fbbf0d2c",
});

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon || '/firebase-logo.png',
        data: payload.data,
        requireInteraction: true,
        actions: [
            {
                action: 'completed',
                title: 'Completed'
            },
            {
                action: 'decline',
                title: 'Decline'
            }
        ]
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification button clicks
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const notificationId = event.notification.data.notification_id;
    const action = event.action;

    if (action === 'completed' || action === 'decline') {
        event.waitUntil(
            fetch('/notification/acknowledge', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId,
                    action: action
                })
            }).then(response => {
                if (response.ok) {
                    console.log('Notification acknowledged:', action);
                }
            }).catch(err => {
                console.error('Error acknowledging notification:', err);
            })
        );
    } else {
        event.waitUntil(
            clients.openWindow('/notifications')
        );
    }
});
