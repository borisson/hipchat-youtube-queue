<?php
/*
REQUIREMENTS
* A custom slash command on a Slack team
* A web server running PHP5 with cURL enabled
USAGE
* Place this script on a server running PHP5 with cURL.
* Set up a new custom slash command on your Slack team: 
  http://my.slack.com/services/new/slash-commands
* Under "Choose a command", enter whatever you want for 
  the command. /isitup is easy to remember.
* Under "URL", enter the URL for the script on your server.
* Leave "Method" set to "Post".
* Decide whether you want this command to show in the 
  autocomplete list for slash commands.
* If you do, enter a short description and usage hint.
*/
# Grab some of the values from the slash command, create vars for post back to Slack
$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
$user = $_POST['user_name'];
$room = $_POST['channel_name'];

# Check the token and make sure the request is from our team
if ($token != 'TOKEN') { #replace this with the token from your slash command configuration page
    $msg = "The token for the slash command doesn't match. Check your script. :(";
    die($msg);
}

$allowed_rooms = [
    'radiowizi',
//    'directmessage', // You can use this for debug
];

if (!in_array($room, $allowed_rooms)) {
    $msg = "Use the #radiowizi room";
    die($msg);
}

require 'vendor/autoload.php';
require 'handlers/SlackRadioHandler.php';

$handler = new \Handlers\SlackRadioHandler('/.*/i');

$message = [
    'url' => $text,
    'user' => $user,
];

$response_array = $handler->Process($message);
header('Content-Type: application/json');

$reply_data = [];

if ($response_array["status_code"] == 1) {
    $reply_data = [
        'response_type'=> 'in_channel',
        'text' => ":thumbsup: " . $response_array['message'],
        'attachments' => [[
            "text" => $response_array['info']['title'],
            "color" => "good",
        ]],
    ];
} else if ($response_array["status_code"] == 2) {
    $reply_data = [
        'response_type' => 'in_channel',
        'text' => ':sadpanda:' . $response_array['message'],
    ];

    if (isset($response_array['info'])) {
        $reply_data['attachments'] = [[
            "text" => $response_array['info']['title'],
            "color" => "danger",
        ]];
    }
}

echo json_encode($reply_data);

die();