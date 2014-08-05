<?php

namespace JadeIT\CrudBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use JadeIT\CrudBundle\Event\CrudEvent;

/**
 * CRUD Service.
 *
 */
class Crud extends ContainerAware
{
    private $entityShortcut;

    private $eventPrefix;

    private $policyClass;

    public function __construct($entityShortcut)
    {
        $this->entityShortcut = $entityShortcut;
    }

    /**
     * Lists all entities.
     */
    public function all()
    {
        $repository = $this->getRepository();
        if ($policy = $this->getPolicy()) {
            $entities = $policy->resolveScope($repository);
        } else {
            $entities = $repository->findAll();
        }

        // Fire the All CRUD Event
        $event = new CrudEvent($entities);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($this->getEventPrefix() . '.all', $event);

        // Maybe add this to a separate (app) listener
        $paginator  = $this->container->get('knp_paginator');
        $entities = $paginator->paginate(
            $entities,
            $this->container->get('request')->query->get('page', 1)/*page number*/,
            10/*limit per page*/
        );

        // Delay returning so that the event can deal with the new entity first
        return $entities;
    }

    /**
     * Creates a new entity.
     */
    public function create($entity)
    {
        $this->checkPolicy('create', $entity);

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
        $entity = $this->getRepository()->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find ' . $this->getEntityShortcut() . '.');
        }
        $this->checkPolicy('read', $entity);

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
        $this->checkPolicy('update', $entity);

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($entity);

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
        $this->checkPolicy('delete', $entity);

        $em = $this->container->get('doctrine')->getManager();
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

    private function getRepository()
    {
        $em = $this->container->get('doctrine')->getManager();
        return $em->getRepository($this->entityShortcut);
    }

    public function checkPolicy($action, $entity)
    {
        $policy = $this->getPolicy($entity);
        if (empty($policy)) {
            return true;
        }
        if ($policy->check($action) === false) {
            throw new AccessDeniedException('Permission Denied');
        }
        return true;
    }

    public function getPolicy($record = null)
    {
        if ($policyClass = $this->getPolicyClass()) {
            $em = $this->container->get('doctrine')->getManager();
            $entity = $em->getClassMetadata($this->entityShortcut);

            $user = $this->container->get("security.context")->getToken()->getUser();
            $record = $record === null ? $entity : $record;
            return new $policyClass($user, $record);
        }
        return null;
    }

    public function getPolicyClass()
    {
        if ($this->policyClass === null) {
            $em = $this->container->get('doctrine')->getManager();
            $entityName = $em->getClassMetadata($this->entityShortcut)->getName();
            //try {
                $this->setPolicyClass($entityName . 'Policy');
            //} catch (\RuntimeException $e) {
                // TODO Check for a default application policy
            //    $this->setPolicyClass('Champs\Bundle\Policy\ApplicationPolicy');
            //}
        }
        return $this->policyClass;
    }

    public function setPolicyClass($className)
    {
        if (class_exists('\\' . $className, true)) {
            $this->policyClass = $className;
        }
    }
}
