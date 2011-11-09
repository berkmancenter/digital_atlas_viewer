<?php

namespace Berkman\AtlasViewerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Berkman\AtlasViewerBundle\Entity\Atlas;
use Berkman\AtlasViewerBundle\Form\AtlasType;

/**
 * Atlas controller.
 *
 */
class AtlasController extends Controller
{
    /**
     * Lists all Atlas entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->findAll();

        return $this->render('BerkmanAtlasViewerBundle:Atlas:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Atlas entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Atlas entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('BerkmanAtlasViewerBundle:Atlas:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Atlas entity.
     *
     */
    public function newAction()
    {
        $entity = new Atlas();
        $form   = $this->createForm(new AtlasType(), $entity);

        return $this->render('BerkmanAtlasViewerBundle:Atlas:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Atlas entity.
     *
     */
    public function createAction()
    {
        $entity  = new Atlas();
        $request = $this->getRequest();
        $form    = $this->createForm(new AtlasType(), $entity);
        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('atlas_show', array('id' => $entity->getId())));
            
        }

        return $this->render('BerkmanAtlasViewerBundle:Atlas:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Atlas entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Atlas entity.');
        }

        $editForm = $this->createForm(new AtlasType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('BerkmanAtlasViewerBundle:Atlas:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Atlas entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Atlas entity.');
        }

        $editForm   = $this->createForm(new AtlasType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('atlas_edit', array('id' => $id)));
        }

        return $this->render('BerkmanAtlasViewerBundle:Atlas:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Atlas entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('BerkmanAtlasViewerBundle:Atlas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Atlas entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('atlas'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
