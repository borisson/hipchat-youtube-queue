<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Genre
 */
class Genre
{
    /**
     * @var integer
     */
    private $genreid;

    /**
     * @var string
     */
    private $genre;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getGenreid()
    {
        return $this->genreid;
    }

    /**
     * Set genre
     *
     * @param string $genre
     * @return Genre
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return string 
     */
    public function getGenre()
    {
        return $this->genre;
    }
}
