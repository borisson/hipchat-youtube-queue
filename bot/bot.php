<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 15/12/2014
 * Time: 21:59
 */

require 'vendor/autoload.php';

$auth = new GorkaLaucirica\HipchatAPIv2Client\Auth\OAuth2('ZW3ohnCRLv7ZZyqQRbiFBBnUuoedq3fSMLKQlHH0');

$browserclient = new Buzz\Client\Curl();
$browserclient->setVerifyPeer(false);
$browser = new Buzz\Browser($browserclient);

$client = new GorkaLaucirica\HipchatAPIv2Client\Client($auth, $browser);

$x = new GorkaLaucirica\HipchatAPIv2Client\API\RoomAPI($client);
var_dump($x->getRooms());
