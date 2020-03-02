// Initialize the Firebase app by passing in the messagingSenderId
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
const messaging = firebase.messaging();

  // Add the public key generated from the console here.
  //messaging.usePublicVapidKey('AAAA4LxS8Jg:APA91bF_cIHoXuaDy4UcwuAEiqQ0JW1dtfTPNRxRZ3u0BD6HnOygfp34UOr2LeqtBlhavsgk63ufozil_yobEmF9iBo6H7dYwMC22vCMwpQ0IwBHMpP9dNVMextZPkViQ4LRWtL_-kqx');
  
navigator.serviceWorker.register('firebase-messaging-sw.js')
.then(function (registration) {
    messaging.useServiceWorker(registration);
        
    // Request for permission
    messaging.requestPermission()
    .then(function() {
      console.log('Notification permission granted.');
      // TODO(developer): Retrieve an Instance ID token for use with FCM.
      messaging.getToken()
      .then(function(currentToken) {
        if (currentToken) {
          console.log('Token: ' + currentToken)
          sendTokenToServer(currentToken);
        } else {
          console.log('No Instance ID token available. Request permission to generate one.');
          setTokenSentToServer(false);
        }
      })
      .catch(function(err) {
        console.log('An error occurred while retrieving token. ', err);
        setTokenSentToServer(false);
      });
    })
    .catch(function(err) {
      console.log('Unable to get permission to notify.', err);
    });
});

// Handle incoming messages
messaging.onMessage(function(payload) {
  console.log("Notification received: ", payload);
  toastr.info(payload.notification.body, payload.notification.title);
});

// Callback fired if Instance ID token is updated.
messaging.onTokenRefresh(function() {
  messaging.getToken()
  .then(function(refreshedToken) {
    console.log('Token refreshed.');
    // Indicate that the new Instance ID token has not yet been sent 
    // to the app server.
    setTokenSentToServer(false);
    // Send Instance ID token to app server.
    sendTokenToServer(refreshedToken);
  })
  .catch(function(err) {
    console.log('Unable to retrieve refreshed token ', err);
  });
});

// Send the Instance ID token your application server, so that it can:
// - send messages back to this app
// - subscribe/unsubscribe the token from topics
function sendTokenToServer(currentToken) {
  if (!isTokenSentToServer()) {
    console.log('Sending token to server...');
    // TODO(developer): Send the current token to your server.
    setTokenSentToServer(true);
  } else {
    console.log('Token already sent to server so won\'t send it again ' +
        'unless it changes');
  }
}

function isTokenSentToServer() {
  return window.localStorage.getItem('sentToServer') == 1;
}

function setTokenSentToServer(sent) {
  window.localStorage.setItem('sentToServer', sent ? 1 : 0);
}

