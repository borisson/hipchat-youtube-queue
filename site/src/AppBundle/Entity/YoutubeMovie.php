<?php

namespace AppBundle\Entity;

use \Doctrine\Common\Collections\ArrayCollection;

class YoutubeMovie
{
    private $id;

    private $videoId;
    private $postedTime;
    private $length;
    private $title;
    private $requestname;
    private $startedTime;
    private $skipped = false;
    private $played = false;
    private $force;
    private $genre;

    private $imageLocation = 'images/youtube/';

    public function __construct($videoId, $length, $title, $requestname = NULL, $force = 0)
    {
        $this->videoId = $videoId;
        $this->length = $length;
        $this->title = $title;
        $this->requestname = $requestname;
        $this->force = $force;
        $this->genre = NULL;
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

    public function isPlayed()
    {
        return $this->played;
    }

    public function setPlayed()
    {
        $this->played = true;
    }

    public function setForce($value)
    {
        $this->force = $value;
    }

    public function startPlaying()
    {
        if (is_null($this->startedTime)) {
            $this->startedTime = new \DateTime();
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getStartedTime()
    {
        return $this->startedTime;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDuration()
    {
        return $this->length;
    }

    public function getYoutubeKey()
    {
        return $this->videoId;
    }

    public function getVideo()
    {
      return "https://youtu.be/" . $this->videoId;
    }

    public function getImage()
    {
        $filename = $this->imageLocation.$this->videoId.'.jpg';
        if (file_exists($filename)) {
            return '/'.$filename;
        } else {
            return '/images/default-youtube-image.jpg';
        }
    }

    public function getIframe()
    {
      return '<iframe width="800" height="400" src="https://www.youtube.com/embed/'.$this->videoId.'?autoplay=0x&controls=0&showinfo=0&disablekb=1&modestbranding=1" frameborder="0" allowfullscreen></iframe>';
    }

    public function getRequestName()
    {
      return $this->requestname;
    }

    public function getForce()
    {
        return $this->force;
    }

    public function setGenre(\AppBundle\Entity\Genre $genre){
        $this->genre = $genre;

        return $this;
    }

    public function getGenre() {
        return $this->genre;
    }

    public function getDataForJson()
    {
        $genre = $this->getGenre();

        return array(
            'iframe' => $this->getIframe(),
            'title' => $this->getTitle(),
            'requestname' => $this->getRequestName(),
            'duration' => $this->getDuration(),
            'id' => $this->getId(),
            'youtubekey' => $this->getYoutubeKey(),
            'image' => $this->getImage(),
            'genre' => is_object($genre)?$genre->getGenre():NULL,
        );
    }
}
