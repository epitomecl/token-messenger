# Inssa Mini Project

Token based communication tool

<p align="justify">
A master user can build up a community around himself. After signup a user wait for their account commitment. The master specify user account and token.
Since a user hold an account, he/she can be active, voting for next master, sending and receiving messages.
</p>


<p align="justify">
User can join the community over the login page. After submit the signup form the user get a confirmation email with an link init. 
In 15 minutes the link must be confirmed. After confirmation a temporary password on the login page will be visible.
The password can be changed in case.
</p>

## Installation

<p align="justify">
All files (index.php, cronjob.php, manifest.json) and folders (api, classes, js, images) should be copied in your root or homepage folder.
The cronjob.php file runs the two services for faucet and ballot. The server must support cron jobs running every 4 hours.
</p>

<p align="justify">
In folder install/ there is an token-messenger-database.sql file. This file contains the master account settings, too.
</p>

<p align="justify">
In folder api/include/ is an db.mysql.ini file. Please adjust following settings for your database:
</p>

```
HOST = "localhost"
USER = "xxx"
PASS = "xxx"
NAME = "xxx"
SOCK = "/tmp/mysql5.sock"
```

<p align="justify">
For firebase notification please adjust in folder js/firebase/ these two files (pushNotification.js, serviceWorker.js)
these following lines:
</p>

```
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
```

In file serviceWorker.js inside function 
```
self.addEventListener('notificationclick'...
``` 
please adjust the line matching with 
```
return clients.openWindow('/inssa/')
``` 
with your own target.