<?php

/*
 * This file is part of the MenuBuilderClientBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Wucdbm\Bundle\WucdbmBundle\Form\AbstractType;

class MenuItemParameterChoiceType extends AbstractType {

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'label'              => 'Choices',
            'placeholder'        => 'Choices',
            'invalid_message'    => 'Invalid Choice',
            'expanded'           => false,
            'multiple'           => false,
            'choices_as_values'  => true,
            'translation_domain' => 'messages',
            'choice_label'       => function ($allChoices) {
                return $allChoices;
            },
            'choice_value'       => function ($allChoices) {
                return $allChoices;
            },
            'empty_data'         => null
        ]);
    }

    public function getParent() {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function getName() {
        return 'wucdbm_menu_builder_menu_item_parameter_choice';
    }
}