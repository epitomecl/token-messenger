<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("api/classes/Autoloader.php");

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use modules\Socket;

$text = <<<EOT
<html>
    <head>
        <style>
            input, button { padding: 10px; }
        </style>
    </head>
    <body>
        <input type="text" id="message" autocomplete="off" />
        <button onclick="transmitMessage()">Send</button>
        <script>
            // Create a new WebSocket.
            var socket  = new WebSocket('wss://mariankulisch.de:8080');

            // Define the 
            var message = document.getElementById('message');

            function transmitMessage() {
                socket.send( message.value );
            }

            socket.onmessage = function(e) {
                alert( e.data );
            }
        </script>
    </body>
</html>
EOT;

echo $text;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Socket()
        )
    ),
    8080
);

$server->run();