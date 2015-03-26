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
                        return array("action" => "reply", "data" => array(
                          'msg' => 'This video is not added. Belgium is not allowed. (traantjes2)',
                          'color' => 'red',
                        ));
                    }
                }
            }
        }

        //Embed check
        if(isset($jsondata['accessControl']['embed']) && $jsondata['accessControl']['embed'] != 'allowed'){
            return array("action" => "reply", "data" => array(
              'msg' => 'This video is not added. The video is not embeddable. (traantjes2)',
              'color' => 'red',
            ));
        }

        // Load file that registers the last 10 video's.
        $fileName =  'data/youtubelinks.txt';
        if (file_exists($fileName)) {
            $fileContents = file_get_contents($fileName);
        } else {
            $fileContents = '';
        }

        // Make an array of the file's contents
        $lines = explode("\n", $fileContents);

        // Make sure the array of video's is only 10 items long
        while(count($lines) > 10) {
            array_shift($lines);
        }

        // Check if the current video id is in the last 10 lines
        if (in_array($video_id, $lines)) {
            return array(
              "action" => "reply",
              "data" => array(
                'msg' => 'This video was already queued in the last 10 songs. Spamming is bad, mkay.',
                'color' => 'red',
            ));
        }

        // Add current video to log
        $lines[] = $video_id;

        // Write file again.
        file_put_contents($fileName, implode("\n", $lines));

        makePost($config['radioUrl'] . 'add', [
            'link' => $message['message'],
            'requestname' => $message['from']['name'],
        ]);

        return array("action" => "reply", "data" => array(
          'msg' => 'Added "' . $jsondata['title'] . '" to Radio Wizi',
          'color' => 'green',
        ));

    }
}
