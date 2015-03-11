<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 15/12/2014
 * Time: 21:59
 */

require 'vendor/autoload.php';
require 'handlers/BadgeHandler.php';

$handlers = array(
  new \Handlers\BadgeHandler('/badge/i')
);

$json = file_get_contents("php://input");
file_put_contents('/tmp/hipchat.txt', $json);

$arr = json_decode($json, true);

LogMe($json);
LogMe(print_r($arr, true));

switch ($arr["event"]) {

    case 'room_message':

        // check all handlers
        foreach ($handlers as $handler) {
            if (preg_match(
              $handler->pattern,
              $arr["item"]["message"]["message"]
            )) {
                LogMe("Handler matches");
                $ret = $handler->Process($arr["item"]["message"]);

                LogMe("Handler reply: ".print_r($ret, true));

                if (isset($ret["action"])) {
                    switch ($ret["action"]) {
                        case 'reply':
                            // send reply to channel
                            sendRoomNotification(
                              $arr["item"]["room"]["id"],
                              $ret["data"]
                            );
                            break;
                    }
                }
            } else {
                LogMe("Handler does not match");
            }
        }

        break;
}

function sendRoomNotification($room, $msg)
{

    LogMe("Sending message to room $room: $msg");

//    $auth = new GorkaLaucirica\HipchatAPIv2Client\Auth\OAuth2('ZW3ohnCRLv7ZZyqQRbiFBBnUuoedq3fSMLKQlHH0');
    $auth = new GorkaLaucirica\HipchatAPIv2Client\Auth\OAuth2(getAuth($room));

    $browserclient = new Buzz\Client\Curl();
    $browserclient->setVerifyPeer(false);
    $browser = new Buzz\Browser($browserclient);
    $client = new GorkaLaucirica\HipchatAPIv2Client\Client($auth, $browser);

    $message = new \GorkaLaucirica\HipchatAPIv2Client\Model\Message();
    $message->setMessage($msg);
    $message->setMessageFormat('text');

    $x = new GorkaLaucirica\HipchatAPIv2Client\API\RoomAPI($client);
    $ret = $x->sendRoomNotification($room, $message);

    LogMe(print_r($ret, true));

}

function LogMe($data)
{
    file_put_contents("data/log.txt", $data."\n", FILE_APPEND);
}

function getAuth($room)
{

    if (file_exists('data/auth/'.$room)) {
        $authfile = $room;
    } elseif (file_exists('data/auth/all')) {
        $authfile = 'all';
    } else {
        return false;
    }

    $data = file_get_contents('data/auth/'.$authfile);
    $json = json_decode($data);
    if ($json->token->expires < time()) {

        // token expired, request new
        LogMe("Room $room Token expired, request new");
        $token = getNewToken(
          $json->install->oauthId,
          $json->install->oauthSecret
        );
        $token->expires = time() + $token->expires_in;
        $json->token = $token;

        LogMe("New Token: ".$json->token->access_token);

        file_put_contents('data/auth/'.$authfile, json_encode($json));

        return $json->token->access_token;

    } else {
        // token valid
        LogMe("Room $authfile Token still valid");

        return $json->token->access_token;
    }

}

function getNewToken($id, $secret)
{

    $post = "grant_type=client_credentials&scope=send_notification";
    $ret = makePost(
      "https://api.hipchat.com/v2/oauth/token",
      $post,
      $id,
      $secret
    );

    LogMe("getNewToken: $ret");

    $json = json_decode($ret);

    return $json;

}

function makePost($url, $postData, $user = "", $pass = "")
{

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    if ($user) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    }

    //execute post
    $result = curl_exec($ch);

    curl_close($ch);

    return $result;
}
