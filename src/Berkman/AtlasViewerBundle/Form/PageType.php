<?php

namespace Berkman\AtlasViewerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class PageType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('epsg_code')
            ->add('id', 'hidden')
        ;
    }

    public function getName()
    {
        return 'page';
    }
}
