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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItemParameter;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\RouteParameter;
use Wucdbm\Bundle\MenuBuilderBundle\Repository\RouteParameterRepository;
use Wucdbm\Bundle\WucdbmBundle\Form\AbstractType;

class MenuItemParameterType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        /** @var MenuItem $item */
        $item = $options['item'];

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($item) {
            $rawData = $event->getData();
            $form = $event->getForm();
            /** @var MenuItemParameter $data */
            $data = $form->getData();
            $requirement = $data->getParameter()->getRequirement();
            $choices = $this->getChoices($requirement);
            if ($choices && $rawData['value']) {
                // if a new choice was added via the select2 box
                // add the value choice field anew with the new choice
                $choices[] = $rawData['value'];
                $form->remove('value');
                $this->addValueChoiceField($form, $choices, $data->getParameter());
                $data->setUseValueFromContext(false);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($item) {
            /** @var MenuItemParameter $data */
            $data = $event->getData();

            $parameter = $data->getParameter();

            if (!$data->getValue() && $parameter->getDefaultValue()) {
                // if empty value was submitted and the RouteParameter has a default value
                // save a copy of the default value to the MenuItemParameter
                // and configure it to check the RouteParameter for default value first
                // before falling back to the current one
                $data->setValue($parameter->getDefaultValue());
                $data->setUseValueFromContext(true);
            }
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($item) {
            /** @var MenuItemParameter $data */
            $data = $event->getData();
            $form = $event->getForm();

            $parameter = $data->getParameter();

            $requirement = $parameter->getRequirement();
            $choices = $this->getChoices($requirement);

            if ($data->getUseValueFromContext() && $parameter->getDefaultValue()) {
                // if editing a MenuItem that has a default value that has not been removed from the RouteParameter
                // You would generally want to update the current default value of RouteParameter in the MenuItemParameter
                // This will also force the form to display an empty text box, or pre-selected placeholder of the select field
                // which will later force it to have the default value copied over if the user did not specify a value
                $data->setValue(null);
            }

            if ($choices) {
                $this->addValueChoiceField($form, $choices, $parameter);
            } else {
                $this->addValueTextField($form, $parameter);
            }

            $form
                ->add('parameter', EntityType::class, [
                    'class'         => 'Wucdbm\Bundle\MenuBuilderBundle\Entity\RouteParameter',
                    'disabled'      => true,
                    'choice_label'  => function (RouteParameter $parameter) {
                        if ($parameter->getRequirement()) {
                            return sprintf('%s (%s)', $parameter->getParameter(), $parameter->getRequirement());
                        }

                        return $parameter->getParameter();
                    },
                    'query_builder' => function (RouteParameterRepository $repository) use ($item) {
                        $route = $item->getRoute();

                        return $repository->getParametersByRouteQueryBuilder($route);
                    },
                    'constraints'   => [
                        new NotBlank([
                            'message' => 'This value is required'
                        ])
                    ]
                ]);
        });
    }

    protected function getParameterName(RouteParameter $parameter) {
        return $parameter->getName() ? $parameter->getName() : $parameter->getParameter();
    }

    protected function createNotBlankConstraint() {
        return new NotBlank([
            'message' => 'This field is required'
        ]);
    }

    protected function createRegexConstaint($requirement = null) {
        $regex = $this->getRegexPattern($requirement);

        $message = sprintf('The given value does not match the requirements: %s', $regex);

        if ('\d+' == $requirement) {
            $message = sprintf('The given value must be numeric. Regex: %s', $regex);
        }

        return new Regex([
            'pattern' => $regex,
            'message' => $message
        ]);
    }

    protected function getRegexPattern($requirement) {
        if (null === $requirement) {
            $requirement = '[^/]++';
        }

        return '#^(' . $requirement . ')$#';
    }

    protected function generate($array) {
        $shouldYield = true;
        $progress = [];
        $remaining = $array;
        foreach ($array as $key => $element) {
            unset($remaining[$key]);
            if (is_array($element)) {
                $shouldYield = false;
                foreach ($element as $subElement) {
                    $copy = array_values($progress);
                    $copy[] = $subElement;
                    foreach ($remaining as $remain) {
                        $copy[] = $remain;
                    }
                    foreach ($this->generate($copy) as $val) {
                        yield $val;
                    }
                }
                break;
            }
            $progress[] = $element;
        }
        if ($shouldYield) {
            yield implode('', $array);
        }
    }

    protected function buildChoicesArray(&$stringArray) {
        $current = '';
        $array = [];
        foreach ($stringArray as $key => $character) {
            unset($stringArray[$key]);
            if ('(' == $character) {
                if ($current) {
                    $array[] = $current;
                    $current = '';
                }
                $array[] = $this->buildChoicesArray($stringArray);
                continue;
            }
            if (')' == $character) {
                if ($current) {
                    $array[] = $current;
                    $current = '';
                }
                break;
            }
            if ('|' == $character) {
                if ($current) {
                    $array[] = $current;
                    $current = '';
                }
                continue;
            }
            $current .= $character;
        }

        if ($current) {
            $array[] = $current;
        }

        return $array;
    }

    protected function buildRequirementsArray($string) {
        $current = '';
        $level = 0;
        $array = [];

        foreach ($string as $key => $character) {
            if ('(' == $character) {
                $level++;
            }
            if (')' == $character) {
                $level--;
            }
            if (0 === $level && '|' == $character) {
                if ($current) {
                    $array[] = $current;
                    $current = '';
                }
                continue;
            }
            $current .= $character;
        }

        if ($current) {
            $array[] = $current;
        }

        return $array;
    }

    protected function getChoices($requirement) {
        if (false === strpos($requirement, '|')) {
            return [];
        }

        $choices = [];

        $arr = str_split($requirement);
        $requirements = $this->buildRequirementsArray($arr);

        foreach ($requirements as $req) {
            if (false !== strpbrk($req, '.+*?')) {
                // maybe figure out how to handle these types of requirements
                continue;
            }
            if (false === strpos($req, '|')) {
                $choices[] = $req;
                continue;
            }
            if (false !== strpbrk($req, '()')) {
                $arr = str_split($req);
                $test = $this->buildChoicesArray($arr);
                $generated = $this->generate($test);
                foreach ($generated as $choice) {
                    $choices[] = $choice;
                }
            }
        }

        return $choices;
    }

    protected function addValueChoiceField(FormInterface $form, $choices, RouteParameter $parameter) {
        $parameterName = $parameter->getName() ? $parameter->getName() : $parameter->getParameter();
        $placeholder = 'Please select one or create a new value if allowed by requirement';
        if ('_locale' == $parameter->getParameter() && $parameter->getDefaultValue()) {
            $placeholder = sprintf('Always use the current locale', $parameter->getDefaultValue());
        } elseif ($parameter->getDefaultValue()) {
            $placeholder = sprintf('Use default (currently  %s)', $parameter->getDefaultValue());
        }
        $options = [
            'label'       => sprintf('Value for parameter "%s"', $parameterName),
            'placeholder' => $placeholder,
            'choices'     => $choices,
            'constraints' => [
                $this->createRegexConstaint($parameter->getRequirement())
            ],
            'required'    => false
        ];
        if (!$parameter->getDefaultValue()) {
            $options['constraints'][] = $this->createNotBlankConstraint();
            $options['required'] = true;
        }
        $form->add('value', MenuItemParameterChoiceType::class, $options);
    }

    protected function addValueTextField(FormInterface $form, RouteParameter $parameter) {
        $constraints = [
            $this->createRegexConstaint($parameter->getRequirement())
        ];

        if (!$parameter->getDefaultValue()) {
            $constraints[] = $this->createNotBlankConstraint();
        }

        $parameterName = $this->getParameterName($parameter);
        $defaultValue = $parameter->getDefaultValue();

        $form
            ->add('value', TextType::class, [
                'label'       => $defaultValue ? sprintf('Value for "%s", leave blank to use default (current: %s)', $parameterName, $defaultValue) : sprintf('Value for "%s"', $parameterName),
                'attr'        => [
                    'placeholder' => $defaultValue ? sprintf('Value for "%s", leave blank to use default (current: %s)', $parameterName, $defaultValue) : $parameterName
                ],
                'constraints' => $constraints,
                'required'    => $defaultValue ? false : true
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options) {
        /** @var MenuItemParameter $parameter */
        $parameter = $form->getData();
        $view->vars = array_merge($view->vars, [
            'parameter' => $parameter->getParameter()->getParameter()
        ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItemParameter',
            'label'      => false
        ])->setRequired([
            'item'
        ]);
    }

    public function getBlockPrefix() {
        return 'wucdbm_menu_builder_client_menu_item_parameter';
    }
}