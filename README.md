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
For firebase notification please adjust in folder js/firebase/ these two files (pushNotification.js, serviceWorker.js).
These following lines should be contain your firebase config data. These data available under your firebase project settings 
in Firebase Web App section.
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

## Beginner

<p align="justify">
After calling the homepage the user will be guided to combined login page. New user sign up with username and email address. 
For other community members an short introduction (about me) is helpful. After submitting the sign up page in case of success
an request for checking incomming emails appears. An timeslot of 15minutes gives the user the possibility to confirm the sign up process.
The user has to call the given link and follow the instruction on the login page. A temporary password is printed on the login page.
After successful login the new user can change their password.
</p>

<p align="justify">
So long the new user is not confirmed by the community, the new user can not participate. 
The new user can only waiting for confirmation by community and adjust the profile.
</p>

## Requirements
<p align="justify">
Now the communication over side channels are necessary. Because of set up the user account the master of the system should be informed about your 
account name, token name, token symbol, token image (256x256, png).
</p>
```
token image (256x256, png)
account name (Blueberry)
token name (e.g. Euro)
token symbol (e.g. EUR)
```
<p align="justify">
The user defined itself over these attributes. All these attributes are possible to change later. 
</p>

## Confirmed
<p align="justify">
After confirmation the user is now a full member with an personal user account. 
Now a full member can have a look into Tx History, User History and Community.
My Page has changed also. On My Page the user see an overview about all own accounts and all outgoing and incomming messages.
</p>

<p align="justify">
The user has the possibility to send direct messages or verified messages. 
Verified messages should be confirmed or refused by receiving person (account).
Confirmed messages transfering the included token to the receiving account.
Refused messages withdrawal the token back to the sender.
</p>

