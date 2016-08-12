<?php

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class NameType extends AbstractType {

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'label'       => 'Display name - this will be displayed as the text of the generated link',
            'attr'        => [
                'placeholder' => 'Display name'
            ],
            'constraints' => [
                new NotBlank()
            ]
        ]);
    }

    public function getParent() {
        return TextType::class;
    }

    public function getBlockPrefix() {
        return 'menu_builder_client_bundle_form_menu_item_name';
    }

}