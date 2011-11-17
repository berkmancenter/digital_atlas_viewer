<?php

namespace Berkman\AtlasViewerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Process\Process;

use Berkman\AtlasViewerBundle\Entity\Atlas;
use Berkman\AtlasViewerBundle\Entity\TilingJob;
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
            $user = $this->get('security.context')->getToken()->getUser();
            $entity->setOwner($user);
            $entity->setCreated(new \DateTime('now'));
            $entity->setUpdated(new \DateTime('now'));

            $em->persist($entity);
            $em->flush();
            $request->getSession()->setFlash('notice', 'New atlas "' . $entity->getName() . '" created.');
            
            // creating the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($entity);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            // grant owner access
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);


            return $this->render('BerkmanAtlasViewerBundle:Atlas:pending.html.twig', array('email' => $user->getEmail()));
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

    /**
     * Recreates the tiles of the atlas
     *
     */
    public function recreateTilesAction($overwriteOld = true)
    {
            $this->get('kernel')->getRootDir();
        $command = $_SERVER['DOCUMENT_ROOT'] . '/DAV/app/console atlas_viewer:import ' . $entity->getId() . ' ' . $entity->getUrl() . ' ' . $entity->getDefaultEpsgCode() . ' ' . $_SERVER['DOCUMENT_ROOT'] . '/DAV/web/tiles/' . $entity->getId();
        error_log($command);
        $process = new Process($command);
        $process->setTimeout(6 * 60 * 60);
        $process->run();

    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
