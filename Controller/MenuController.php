<?php

/*
 * This file is part of the MenuBuilderClientBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\Menu;
use Wucdbm\Bundle\MenuBuilderBundle\Filter\Menu\MenuFilter;
use Wucdbm\Bundle\MenuBuilderBundle\Form\Menu\MenuFilterType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\CreateType;
use Wucdbm\Bundle\WucdbmBundle\Controller\BaseController;

class MenuController extends BaseController {

    public function listAction(Request $request) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $filter = new MenuFilter();
        $pagination = $filter->getPagination()->enable();
        $filterForm = $this->createForm(MenuFilterType::class, $filter);
        $filter->load($request, $filterForm);
        $menus = $repo->filter($filter);
        $data = [
            'menus'      => $menus,
            'filter'     => $filter,
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/list/list.html.twig', $data);
    }

    public function refreshListRowAction(Menu $menu) {
        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/list/list_row.html.twig', $data);
    }

    public function updateNameAction($id, Request $request) {
        $post = $request->request;
        // name, value, pk
        $name = $post->get('value', null);

        if (null === $name) {
            return new Response('Error - Empty value', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);
        $menu->setName($name);
        $repo->save($menu);

        return new Response();
    }

    public function createAction(Request $request) {
        $menu = new Menu();
        $form = $this->createForm(CreateType::class, $menu);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $manager = $this->container->get('wucdbm_menu_builder.manager.menus');
            $manager->save($menu);

            return $this->redirectToRoute('wucdbm_menu_builder_client_menu_edit', [
                'id' => $menu->getId()
            ]);
        }

        $data = [
            'form' => $form->createView(),
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/create.html.twig', $data);
    }

    public function editAction($id) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/edit.html.twig', $data);
    }

    public function refreshNestableAction(Menu $menu) {
        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/edit/nestable.html.twig', $data);
    }

    public function updateNestableAction(Menu $menu, Request $request) {
        $order = $request->request->get('order');

        if (!is_array($order)) {
            return $this->json([
                'witter' => [
                    'title' => 'Error',
                    'text'  => 'Submitted is not an array.'
                ]
            ]);
        }

        try {
            $manager = $this->container->get('wucdbm_menu_builder_client.manager.order');
            $manager->order($menu, $order);

            return $this->json([
                'witter' => [
                    'title' => 'Success',
                    'text'  => 'Menu reordered successfully'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'witter' => [
                    'title' => sprintf('Error: Uncaught %s', get_class($e)),
                    'text'  => $e->getMessage()
                ]
            ]);
        }
    }

}