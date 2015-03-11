<?php

require_once 'vendor/autoload.php';

$json = file_get_contents("php://input");
file_put_contents('/tmp/hipchat.txt', $json);

$auth["install"] = json_decode($json);

$arr = json_decode($json, true);

// id
$id = $arr["oauthId"];
$secret = $arr["oauthSecret"];
$url = $arr["capabilitiesUrl"];

// https://api.hipchat.com/v2/oauth/token
$post = "grant_type=client_credentials&scope=send_notification";

$ret = makePost("https://api.hipchat.com/v2/oauth/token", $post, $id, $secret);
file_put_contents('/tmp/hipchat.txt', $ret, FILE_APPEND);

$auth["token"] = json_decode($ret);
$auth["token"]->expires = time() + $auth["token"]->expires_in;

file_put_contents('data/auth/'.$auth["install"]->oauthId, json_encode($auth));

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