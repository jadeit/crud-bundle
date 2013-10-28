<?php

namespace JadeIT\CrudBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

use JadeIT\CrudBundle\Event\CrudEvent;

/**
 * CRUD Service.
 *
 */
class Crud extends ContainerAware
{
    private $entityShortcut;

    private $eventPrefix;

    public function __construct($entityShortcut)
    {
        $this->entityShortcut = $entityShortcut;
    }

    /**
     * Lists all entities.
     */
    public function all()
    {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository($this->entityShortcut)->findAll();

        // Fire the All CRUD Event
        $event = new CrudEvent($entities);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.all', $event);

        // Delay returning so that the event can deal with the new entity first
        return $entities;
    }

    /**
     * Creates a new entity.
     */
    public function create($entity)
    {
        $em = $this->container->get('doctrine')->getManager();
        $em->persist($entity);

        // Fire the New CRUD Event
        $event = new CrudEvent($entity);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.create', $event);

        // Delay the flush so that the event can deal with the new entity first
        $em->flush();
    }

    /**
     * Finds and displays an entity.
     *
     */
    public function read($id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $entity = $em->getRepository($this->entityShortcut)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $this->getEntityShortcut() . '.');
        }

        // Fire the New CRUD Event
        $event = new CrudEvent($entity);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.read', $event);

        return $entity;
    }

    /**
     * Edits an existing entity.
     */
    public function update($entity)
    {
        $em->update($entity);

        // Fire the Update CRUD Event
        $event = new CrudEvent($entity);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.update', $event);

        // Delay the flush so that the event can deal with the updated entity first
        $em->flush();
    }

    /**
     * Deletes an entity.
     */
    public function delete($entity)
    {
        $em->remove($entity);

        // Fire the Delete CRUD Event
        $event = new CrudEvent($entity);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.delete', $event);

        // Delay the flush so that the event can deal with the deleted entity first
        $em->flush();
    }

    /**
     * Get entityShortcut
     *
     * Typically VendorSomeBundle:EntityName
     *
     * @return string
     */
    public function getEntityShortcut() {
        return $this->entityShortcut;
    }

    /**
     * Set entityShortcut
     *
     * Typically VendorSomeBundle:EntityName
     *
     * @param string $EntityShortcut
     */
    public function setEntityShortcut($entityShortcut) {
        $this->entityShortcut = $entityShortcut;

        return $this;
    }

    /**
     * Get eventPrefix
     *
     * @return string
     */
    public function getEventPrefix() {
        if (empty($this->eventPrefix)) {
            $parts = preg_split('/([A-Z][^A-Z]*)/', $this->entityShortcut, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $parts = array_filter($parts, function($elm) { return $elm !== 'Bundle:' && empty($elm) === false; });
            $parts = array_map('strtolower', $parts);
            $this->eventPrefix = implode('.', $parts);
        }
        return $this->eventPrefix;
    }

    /**
     * Set eventPrefix
     *
     * @param string $eventPrefix
     */
    public function setEventPrefix($eventPrefix) {
        $this->eventPrefix = $eventPrefix;

        return $this;
    }
}
