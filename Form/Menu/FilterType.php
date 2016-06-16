<?php

/*
 * This file is part of the MenuBuilderBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\Route;
use Wucdbm\Bundle\MenuBuilderBundle\Repository\RouteRepository;
use Wucdbm\Bundle\WucdbmBundle\Form\Filter\BaseFilterType;

class FilterType extends BaseFilterType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('route', 'Wucdbm\Bundle\WucdbmBundle\Form\Filter\EntityFilterType', [
                'class'         => 'Wucdbm\Bundle\MenuBuilderBundle\Entity\Route',
                'query_builder' => function (RouteRepository $repository) {
                    return $repository->getPublicRoutesQueryBuilder();
                },
                'choice_label'  => function (Route $route) {
                    if ($route->getName()) {
                        return sprintf('%s (%s)', $route->getName(), $route->getRoute());
                    }

                    $routeName = str_replace(['_', '.'], ' ', $route->getRoute());
                    $words = explode(' ', $routeName);
                    foreach ($words as $key => $word) {
                        $words[$key] = ucfirst($word);
                    }

                    return sprintf('%s (%s)', implode(' ', $words), $route->getRoute());
                },
                'placeholder'   => 'Route'
            ])
            ->add('name', 'Wucdbm\Bundle\WucdbmBundle\Form\Filter\TextFilterType', [
                'placeholder' => 'Name'
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Wucdbm\Bundle\MenuBuilderBundle\Filter\Menu\MenuFilter'
        ));
    }

}
