<?php

namespace AppBundle\Entity;

class YoutubeMovie
{
    private $videoId;
    private $postedTime;
    private $length;
    private $title;
    private $skipped = false;

    public function __construct($videoId, $length, $title)
    {
        $this->videoId = $videoId;
        $this->length = $length;
        $this->title = $title;

        $this->postedTime = new \DateTime();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function skipVideo()
    {
        $this->skipped = true;
    }

    public function getVideo()
    {
      return "https://youtu.be/" . $this->videoId;
    }

    public function getImage()
    {
      return "http://img.youtube.com/vi/$this->videoId/0.jpg";
    }

    public function getIframe()
    {
      return '<iframe width="800" height="400" src="https://www.youtube.com/embed/'.$this->videoId.'" frameborder="0" allowfullscreen></iframe>';

    }


}
