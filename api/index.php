<?php
header("Content-Type: application/json");
echo json_encode([
    "message" => "Welcome to Muskan Chatbot PHP API!",
    "endpoints" => [
        "POST /chat" => "Interact with Muskan by sending JSON body: {'message': 'Hello'}"
    ]
]);
