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
            ->add('created')
            ->add('updated')
            ->add('owner')
        ;
    }

    public function getName()
    {
        return 'berkman_atlasviewerbundle_atlastype';
    }
}
