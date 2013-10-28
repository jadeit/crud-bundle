<?php

namespace JadeIT\ApplicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Record
 */
class Record
{
    /**
     * @var integer
     */
    private $id;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
