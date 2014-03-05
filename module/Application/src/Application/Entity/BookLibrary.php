<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BookLibrary
 *
 * @ORM\Table(name="book")
 * @ORM\Entity
 */
class BookLibrary
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $title
     * @ORM\Column(name="book_title", type="text", nullable=true)
     */
    private $title;
    
    /**
     * @var string $author
     * @ORM\Column(name="author", type="text", nullable=true)
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="release_date", type="date", nullable=true)
     */
    private $releaseDate;
    
    /**
     * @var string $keywords
     * @ORM\Column(name="keywords", type="text", nullable=true)
     */
    private $keywords;
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $status
     * @return BookLibrary
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * Set author
     *
     * @param string $author
     * @return BookLibrary
     */
    public function setAuthor($author)
    {
    	$this->author = $author;
    
    	return $this;
    }
    
    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
    	return $this->author;
    }
	
    /**
     * Set releaseDate
     *
     * @param \DateTime $releaseDate
     * @return BookLibrary
     */
    public function setReleaseDate($releaseDate)
    {
    	$this->releaseDate = $releaseDate;
    
    	return $this;
    }
    
    /**
     * Get releaseDate
     *
     * @return \DateTime
     */
    public function getReleaseDate()
    {
    	return $this->releaseDate;
    }
    
    /**
     * Set keywords
     *
     * @param string $keywords
     * @return BookLibrary
     */
    public function setKeywords($keywords)
    {
    	$this->keywords = $keywords;
    
    	return $this;
    }
    
    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
    	return $this->keywords;
    }
    
}
