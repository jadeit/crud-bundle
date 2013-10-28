<?php
namespace JadeIT\CrudBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CrudEvent extends Event
{
    private $entity;

    /**
     * Get entity
     *
     * @return object
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * Set entity
     *
     * @param object $entity
     */
    public function setEntity($entity) {
        $this->entity = $entity;

        return $this;
    }
}
