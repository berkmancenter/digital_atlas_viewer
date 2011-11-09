<?php

namespace Berkman\AtlasViewerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('BerkmanAtlasViewerBundle:Default:index.html.twig', array('name' => $name));
    }
}
