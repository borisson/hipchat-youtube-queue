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
            'http://gdata.youtube.com/feeds/api/videos/' . $video_id . '?v=2&alt=jsonc&prettyprint=true',
            [
                'headers' => ['Content-Type' => 'text/json'],
                'verify' => false,
                'timeout' => 5,
            ]
        );

        $json = $response->json();
        $jsondata = $json['data'];

        //Check Belgian country check
        if(isset($jsondata['restrictions'])){
          foreach($jsondata['restrictions'] as $restriction){
              if($restriction['type'] == 'country' && $restriction['relationship'] == 'deny'){
                  if (strpos($restriction['countries'],'BE') !== false) {
                      return array("action" => "reply", "data" => 'This video is not added. Belgium is not allowed. :(');
                  }
              }
          }
        }

        //Embed check
        if(isset($jsondata['accessControl']['embed']) && $jsondata['accessControl']['embed'] != 'allowed'){
            return array("action" => "reply", "data" => 'This video is not added. The video is not embeddable. :(');
        }

        makePost($config['radioUrl'] . 'add', [
            'link' => $message['message'],
            'requestname' => $message['from']['name'],
        ]);

        return array("action" => "reply", "data" => 'Added "' . $jsondata['title'] . '" to Radio Wizi');

    }
}
