// Initialize the Firebase app by passing in the messagingSenderId
var config = {
    apiKey: "AIzaSyDzmO-...",
    authDomain: "inssaminiproject.firebaseapp.com",
    databaseURL: "https://inssaminiproject.firebaseio.com",
    projectId: "inssaminiproject",
    storageBucket: "inssaminiproject.appspot.com",
    messagingSenderId: "965232226456",
    appId: "1:965232226456:web:facc3b298da14f1cf5e0ed",
    measurementId: "G-CC1CQ9PY02"
};
firebase.initializeApp(config);

const messaging = firebase.messaging(),
      pushBtn   = document.getElementById('push-btn');

let userToken    = null,
    isSubscribed = false;

window.addEventListener('load', () => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('js/firebase/serviceWorker.js', {updateViaCache: 'none'})
            .then(registration => {
                messaging.useServiceWorker(registration);
				initializePush();
            })
            .catch(err => console.log('Service Worker Error', err));
    } else {
        pushBtn.textContent = 'Push not supported.';
    }
})

function initializePush() {
    userToken = localStorage.getItem('pushToken');

    isSubscribed = userToken !== null;
    updateBtn();

    pushBtn.addEventListener('click', () => {
        pushBtn.disabled = true;

        if (isSubscribed) return unsubscribeUser();

        return subscribeUser();
    })
}

function updateBtn() {
    if (Notification.permission === 'denied') {
        pushBtn.textContent = 'Subscription blocked';
        return;
    }

    pushBtn.textContent = isSubscribed ? 'Unsubscribe' : 'Subscribe';
    pushBtn.disabled = false;
}

function subscribeUser() {
    messaging.requestPermission()
        .then(() => messaging.getToken())
        .then(token => {
            updateSubscriptionOnServer(token);
            isSubscribed = true;
            userToken = token;
            localStorage.setItem('pushToken', token);
            updateBtn();
        })
        .catch(err => console.log('Denied', err));
}

function unsubscribeUser() {
    messaging.deleteToken(userToken)
        .then(() => {
            updateSubscriptionOnServer(userToken);
            isSubscribed = false;
            userToken = null;
            localStorage.removeItem('pushToken');
            updateBtn();
        })
        .catch(err => console.log('Error unsubscribing', err));
}

function updateSubscriptionOnServer(token) {
	var formData = new FormData();
	formData.append("module", "PUSHTOKEN");
	formData.append("pushToken", token);
	
    if (isSubscribed) {
		formData.append("NGINX", "DELETE");
    }
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}
		
	requestPost(query.join("&"));
}

function refreshSubscriptionOnServer(token, cleanup) {
	var formData = new FormData();
	formData.append("module", "PUSHTOKEN");
	formData.append("pushToken", token);
	
    if (cleanup) {
		formData.append("NGINX", "DELETE");
    }
	
	var query = new Array();
	for (var pair of formData.entries()) {
		query.push(pair[0] + "=" + pair[1]); 
	}
		
	requestPost(query.join("&"));
}

// Handle incoming messages
messaging.onMessage(function(payload) {
  console.log("Notification received: ", payload);
});

// Callback fired if Instance ID token is updated.
messaging.onTokenRefresh(function() {
	messaging.getToken()
	.then(function(refreshedToken) {
		console.log('Token refreshed.', refreshedToken);
		
		// Send Instance ID token to app server.
		if (userToken !== refreshedToken) {
			refreshSubscriptionOnServer(userToken, true);
			refreshSubscriptionOnServer(refreshedToken, false);
		}
	})
	.catch(err => console.log('Unable to retrieve refreshed token', err));
});