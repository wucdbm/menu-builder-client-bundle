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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Wucdbm\Bundle\WucdbmBundle\Form\AbstractType;

//use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ExternalUrlItemType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', NameType::class)
            ->add('url', UrlType::class);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => MenuItem::class
        ]);
    }

    public function getName() {
        return 'wucdbm_menu_builder_menu_item_url_type';
    }
}