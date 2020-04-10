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
account name (e.g. Blueberry)
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

## API Overview

<p align="justify">
The API is callable by /inssa/api/ and without any param a test form comes up.
In this test form every method and param set are testable.
</p>

### AccountSuspended

<p align="justify">
Set the given account ID on suspended or reactivate a suspended account by account ID.
The column param must hold the column "suspended" for specifying the table column.
</p>

POST Input: 
```
accountId (int) 4, column (string) "suspended", checked (int) 0 or 1
```

POST Output:
```
{accountId: 4}
```

### Ballot

<p align="justify">
Was designed for voting next master of community. 
Only member can vote. Member are users with at least one account.
This Method is called by cron-job or manually.
The main function contains clean up old votes (older then 30 days) and determined the winner of current vote.
</p>

### ChatMessage

<p align="justify">
This Module deliver the chat room list, when receiver is unknown.
If receiver is known, then all messages between sender and receiver will shown.
</p>

GET Input:
```
userId (int), page (int), itemsPerPage (int), senderId (int), receiverId (int), year (int), month (int)
```

GET Output:

<p align="justify">
Json-object contains also a hashValue based on current data set (chat rooms or messages).  for checking data changes.
</p>

### Community

<p align="justify">
Community module gives an overview about all community members and information about current voting.
</p>

### DirectTokenTransfer

<p align="justify">
Direct transfer of quantity of owned unique token with specific message and without possibility of cancelation.
</p>

### Faucet

<p align="justify">
Minted new token and update the account balance and submit a direct transaction with automatic reference.
</p>

### NumberOfTokens

<p align="justify">
A Token Trading Status information is accessible to all users. 
Gives a Number of tokens held by a specific account.
</p>

GET Input:
```
accountId (int), year (int), month (int)
```

### PendingTransaction

List all pending transaction based on user between his sender accounts and receiver accounts.

### PushNotification

<p align="justify">
Firebase push notification service. This module send a push notification 
to a device specified by current device token.
</p>

### PushToken

Service to register or delete a token for push notification in database.

### Socket

<p align="justify">
Unfortunately this Service in combination with ratchet server was not working.
Web-Sockets were not supported by current webhoster package.
</p>

### TokenTransactionDetail

<p align="justify">
A Token Trading Detail information is accessible to all users.
It contains a Token transaction history sent and received by a specific account.
</p>

### TokenTransactionHistory

<p align="justify">
A Token Trading History information is accessible to all users.
It contains Token transaction history sent and received by a specific account.
The list is based on current month and year, if month or year are not given.
</p>

### TotalUserTokenHistory

Total user token history about all accounts.

### TotalUserTokenHoldings

Provides a list about all unique tokens and received tokens by each account.

### UserAccount

<p align="justify">
Provides a list over all user accounts by given user ID. 
The result includes information about total unique token, received tokens, send tokens and currently send tokens and remain tokens.
</p>

### VerifiedTokenTransfer

<p align="justify">
Token transfer from one account to another account with prove of agreement and with using escrow account.
</p>