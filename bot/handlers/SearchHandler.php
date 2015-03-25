<?php

namespace Handlers;

use GuzzleHttp\Client as GuzzleClient;

class SearchHandler
{

    public $pattern;

    public function __construct($pattern = "/search/i")
    {
        $this->pattern = $pattern;
    }

    public function Process($message)
    {
        global $config;

        if (preg_match('/^'.$config['botname'].' search (.+)/i', $message['message'], $match)) {

            // search Youtube for given term, only videos
            $client = new GuzzleClient();
            $response = $client->get(
                'https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=3&regionCode=BE&type=video&videoSyndicated=true&q=' . urlencode($match[1]) . '&key=' . $config['youtube_api_key'],
                [
                    'headers' => ['Content-Type' => 'text/json'],
                    'verify' => false,
                    'timeout' => 5,
                ]
            );

            $json = $response->json();

            $results = array();
            foreach ($json["items"] as $item) {
                $results[] = 'http://www.youtube.com/watch?v=' . $item['id']['videoId'] . ' ' . $item['snippet']['title'];
            }

            if (count($results) > 0) {
                return array("action" => "reply", "data" => array(
                    'msg' => implode("\r\n", $results),
                    'color' => 'yellow'
                ));
            } else {
                return array("action" => "reply", "data" => array(
                    'msg' => 'Millions of hours of video, yet your search manages to find none...',
                    'color' => 'red'
                ));
            }

        } else {
            return array("action" => "reply", "data" => array(
                'msg' => 'Oops, something went wrong.',
                'color' => 'red'
            ));
        }

    }
}
