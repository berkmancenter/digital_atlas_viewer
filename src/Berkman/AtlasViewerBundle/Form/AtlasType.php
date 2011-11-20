<?php

namespace Berkman\AtlasViewerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class AtlasType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('url')
            ->add('default_epsg_code')
            ->add('pages', 'collection', array('type' => new PageType()))
        ;
    }

    public function getName()
    {
        return 'atlas';
    }
}
