<?php

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlType extends AbstractType {

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'label' => 'Please provide an external URL',
            'attr'  => [
                'placeholder' => 'Please provide an external URL'
            ]
        ]);
    }

    public function getParent() {
        return \Symfony\Component\Form\Extension\Core\Type\UrlType::class;
    }

    public function getBlockPrefix() {
        return 'menu_builder_client_bundle_form_menu_item_url';
    }

}