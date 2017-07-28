<?php

namespace Handlers;

use GuzzleHttp\Client as GuzzleClient;

class SlackRadioHandler
{

    public $pattern;
    private $ytkey = 'YOUTUBEKEY';

    public function __construct($pattern = "/.*/")
    {
        $this->pattern = $pattern;
    }

    public function Process($message)
    {
        global $config;

        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $message['url'], $match)) {
            if(!isset($match[1])){
                return [
                    'status_code' => 2,
                    'message' => 'Doesn\'t match youtube url',
                ];
            }
            $video_id = $match[1];
        }

        if (!isset($video_id)) {
            return [
                'status_code' => 2,
                'message' => 'Doesn\'t match youtube url',
            ];
        }

        $youtubeinfo = $this->parseYoutubeInfo($video_id);

        if($youtubeinfo['playable'] == false){
            if($youtubeinfo['embeddable'] == false){
                return [
                    'status_code' => 2,
                    'message' => 'This video is not embeddable.',
                    'info' => $youtubeinfo
                ];
            }else if($youtubeinfo['restriction'] == true){
                return [
                    'status_code' => 2,
                    'message' => 'This video is not embeddable.',
                    'info' => $youtubeinfo
                ];
            }
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
            return [
                'status_code' => 2,
                'message' => 'This video was already queued in the last 10 songs.',
                'info' => $youtubeinfo
            ];
        }

        // Add current video to log
        $lines[] = $video_id;

        // Write file again.
        file_put_contents($fileName, implode("\n", $lines));

        $this->makePost($config['radioUrl'] . 'add', [
            'link' => $message['url'],
            'requestname' => $message['user'],
        ]);

        return [
            'status_code' => 1,
            'message' => 'Added to Radio Wizi',
            'info' => $youtubeinfo
        ];
    }

    private function parseYoutubeInfo($videoId){
        $client = new GuzzleClient();
        $response = $client->get(
            'https://www.googleapis.com/youtube/v3/videos?id=' . $videoId .
            '&part=contentDetails,status,snippet&prettyPrint=true&&videoEmbeddable=true
            &key='.$this->ytkey,
            [
                'headers' => ['Content-Type' => 'text/json'],
                'verify' => false,
                'timeout' => 5,
            ]
        );

        $json = $response->json();

        if(isset($json['items'][0]['kind']) && $json['items'][0]['kind'] == 'youtube#video'){
            $video = $json['items'][0];
            $duration_raw = $video['contentDetails']['duration'];
            $duration = $this->PTtoSec($duration_raw);

            $restrictions = false;
            $playable = true;
            $embeddable = true;
            $title = 'Unknown title';

            if(isset($video['contentDetails']['regionRestriction'])){
                if(!isset($video['contentDetails']['regionRestriction']['allowed']['BE'])){
                    if(!isset($video['contentDetails']['regionRestriction']['blocked'])){
                        $restrictions = true;
                        $playable = false;
                    }
                }

                if(isset($video['contentDetails']['regionRestriction']['blocked']['BE'])){
                    $restrictions = true;
                    $playable = false;
                }
            }

            if(isset($video['status']['embeddable']) && $video['status']['embeddable'] == false){
                $embeddable = false;
                $playable = false;
            }

            if(isset($video['snippet']['title'])){
                $title = $video['snippet']['title'];
            }

            if(isset($video['snippet']['thumbnails']['high']['url'])) {
                $img = $video['snippet']['thumbnails']['high']['url'];
            }else{
                $img = $video['snippet']['thumbnails']['default']['url'];
            }

            return array(
                'title' => $title,
                'video_id' => $videoId,
                'duration' => $duration,
                'playable' => $playable,
                'img' => $img,
                'restriction' => $restrictions,
                'embeddable' => $embeddable,
                'ytresponse' => $video,
            );
        }

        return NULL;
    }

    private function PTtoSec($pt){
        $dateint = new \DateInterval($pt);
        $hourstosec = $dateint->h * 3600;
        $minutestosec = $dateint->i * 60;
        $duration = $hourstosec + $minutestosec + $dateint->s;
        return $duration;
    }


    private function makePost($url, $postData, $user = "", $pass = "")
    {

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
}
