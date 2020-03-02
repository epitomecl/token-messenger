// Give the service worker access to Firebase Messaging.
importScripts('https://www.gstatic.com/firebasejs/7.8.1/firebase-app.js')
importScripts('https://www.gstatic.com/firebasejs/7.8.1/firebase-messaging.js')

// Initialize the Firebase app in the service worker by passing in the messagingSenderId.
var config = {
	apiKey: "AIzaSyDzmO-0EXYWmSHsqUM7PPIa7mzYwwMzv3Y",
	authDomain: "inssaminiproject.firebaseapp.com",
	databaseURL: "https://inssaminiproject.firebaseio.com",
	projectId: "inssaminiproject",
	storageBucket: "inssaminiproject.appspot.com",
	messagingSenderId: "965232226456",
	appId: "1:965232226456:web:facc3b298da14f1cf5e0ed",
	measurementId: "G-CC1CQ9PY02"
};
firebase.initializeApp(config);

// Retrieve an instance of Firebase Data Messaging so that it can handle background messages.
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function(payload) {
	console.log('[Service Worker] BackgroundMessageHandler Push Received.');
	console.log('[Service Worker] BackgroundMessageHandler handle this data: ' + event.data.text());
	
    const title = 'Inssa Message: ' + payload.notification.title;
	const body = payload.notification.body;
	const options = {
		body: body,
		icon: '/images/icon.png',
		badge: '/images/badge.png',
		sound: 'default'
	};
  
	return self.registration.showNotification(title, options);
});

self.addEventListener('push', function(event) {
	console.log('[Service Worker] Push Received.');
	console.log('[Service Worker] Push had this data: ' + event.data.text());
	
	const payload = event.data.json();
    const title = 'Inssa Message: ' + payload.notification.title;
	const body = payload.notification.body;
	const options = {
		body: body,
		icon: '/images/icon.png',
		badge: '/images/badge.png',
		sound: 'default'
	};

	event.waitUntil(self.registration.showNotification(title, options));
});

// self.addEventListener('notificationclick', function(event) {
  // console.log('[Service Worker] Notification click Received.');

  // event.notification.close();

  // event.waitUntil(
	// clients.openWindow('https://mariankulisch.de/inssa/')
  // );
// });

self.addEventListener('notificationclick', function(event) {
	console.log('On notification click: ', event.notification.tag);
	// Android doesn’t close the notification when you click on it
	// See: http://crbug.com/463146
	event.notification.close();

	// This looks to see if the current is already open and
	// focuses if it is
	event.waitUntil(clients.matchAll({
		type: 'window'
	}).then(function(clientList) {
		for (var i = 0; i < clientList.length; i++) {
			var client = clientList[i];
			if (client.url === '/' && 'focus' in client) {
				return client.focus();
			}
		}
		if (clients.openWindow) {
			return clients.openWindow('/inssa/');
		}
	}));
});

self.addEventListener('pushsubscriptionchange', function() {
	// remove the entry from DB
	
});
