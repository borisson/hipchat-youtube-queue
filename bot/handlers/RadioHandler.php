<?php

namespace Handlers;

use GuzzleHttp\Client as GuzzleClient;

class RadioHandler
{

    public $pattern;

    public function __construct($pattern = "/.*/")
    {
        $this->pattern = $pattern;
    }

    public function Process($message)
    {
        global $config;

        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $message['message'], $match)) {
          if(!isset($match[1])){
            throw new \InvalidArgumentException("Doesn't match youtube url");
          }
          $video_id = $match[1];
        }

        if (!isset($video_id)) {
          throw new \InvalidArgumentException("Doesn't match youtube url");
        }

        $client = new GuzzleClient();

        $response = $client->get(
          'http://gdata.youtube.com/feeds/api/videos/' . $video_id,
          [
            'headers' => ['Content-Type' => 'text/xml'],
            'verify' => false,
            'timeout' => 5,
          ]
        );

        $xml = $response->xml();


        makePost($config['radioUrl'] . 'add', [
            'link' => $message['message'],
            'requestname' => $message['from']['name'],
        ]);

        return array("action" => "reply", "data" => 'Added "' . $xml->title . '" to Radio Wizi');

    }
}
