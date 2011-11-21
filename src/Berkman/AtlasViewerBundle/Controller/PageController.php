<?php

namespace Berkman\AtlasViewerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Berkman\AtlasViewerBundle\Entity\Page;
use Berkman\AtlasViewerBundle\Form\PageType;
use Berkman\AtlasViewerBundle\Entity\Job;

/**
 * Page controller.
 *
 */
class PageController extends Controller
{
    /**
     * Lists all Page entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entities = $em->getRepository('BerkmanAtlasViewerBundle:Page')->findAll();

        return $this->render('BerkmanAtlasViewerBundle:Page:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Page entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Page')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('BerkmanAtlasViewerBundle:Page:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Page entity.
     *
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createForm(new PageType(), $entity);

        return $this->render('BerkmanAtlasViewerBundle:Page:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Page entity.
     *
     */
    public function createAction()
    {
        $entity  = new Page();
        $request = $this->getRequest();
        $form    = $this->createForm(new PageType(), $entity);
        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('page_show', array('id' => $entity->getId())));
            
        }

        return $this->render('BerkmanAtlasViewerBundle:Page:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Page')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $editForm = $this->createForm(new PageType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('BerkmanAtlasViewerBundle:Page:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Page entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $entity = $em->getRepository('BerkmanAtlasViewerBundle:Page')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $editForm   = $this->createForm(new PageType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bindRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('page_edit', array('id' => $id)));
        }

        return $this->render('BerkmanAtlasViewerBundle:Page:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function generateTilesAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $rootDir = $this->get('kernel')->getRootDir();
        $command = 'nice ' . $rootDir . '/console atlas_viewer:page:generate_tiles ' . $id . ' ' . $rootDir . '/../tmp ' . $rootDir . '/../web/tiles -m';
        $job = new Job($command, 1 * 60 * 60);
        $em->persist($job);
        $em->flush();
        return $this->render('BerkmanAtlasViewerBundle:Atlas:pending.html.twig');
    }

    /**
     * Deletes a Page entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bindRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('BerkmanAtlasViewerBundle:Page')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Page entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('page'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
