<?php

namespace JadeIT\CrudBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JadeIT\CrudBundle\Entity\Record;
use JadeIT\CrudBundle\Form\RecordType;

/**
 * Record controller.
 *
 */
class RecordController extends Controller
{

    /**
     * Lists all Record entities.
     *
     */
    public function indexAction()
    {
        $entities = $this->get('jade.i.t.crud')->all();

        return $this->render('JadeITCrudBundle:Record:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Record entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Record();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('jade.i.t.crud')->create($entity);

            return $this->redirect($this->generateUrl('record_show', array('id' => $entity->getId())));
        }

        return $this->render('JadeITCrudBundle:Record:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to create a Record entity.
    *
    * @param Record $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Record $entity)
    {
        $form = $this->createForm(new RecordType(), $entity, array(
            'action' => $this->generateUrl('record_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Record entity.
     *
     */
    public function newAction()
    {
        $entity = new Record();
        $form   = $this->createCreateForm($entity);

        return $this->render('JadeITCrudBundle:Record:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Record entity.
     *
     */
    public function showAction($id)
    {
        $entity = $this->get('jade.i.t.application.crud')->read($id);

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('JadeITCrudBundle:Record:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Record entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('JadeITCrudBundle:Record')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Record entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('JadeITCrudBundle:Record:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Record entity.
    *
    * @param Record $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Record $entity)
    {
        $form = $this->createForm(new RecordType(), $entity, array(
            'action' => $this->generateUrl('record_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Record entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('JadeITCrudBundle:Record')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Record entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->get('jade.i.t.application.crud')->update($entity);

            return $this->redirect($this->generateUrl('record_edit', array('id' => $id)));
        }

        return $this->render('JadeITCrudBundle:Record:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Record entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('JadeITCrudBundle:Record')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Record entity.');
            }

            $this->get('jade.i.t.application.crud')->delete($entity);
        }

        return $this->redirect($this->generateUrl('record'));
    }

    /**
     * Creates a form to delete a Record entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('record_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
