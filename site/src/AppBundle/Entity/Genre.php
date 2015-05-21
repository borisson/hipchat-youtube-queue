<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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

    protected $children;
    private $parent;

    public function __construct() {
        $this->children = new ArrayCollection();
    }

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

    /**
     * Set parent
     */
    public function setParent($parent){
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get Parent
     */
    public function getParent(){
        return $this->parent;
    }
}
