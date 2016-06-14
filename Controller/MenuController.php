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
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Wucdbm\Bundle\MenuBuilderBundle\Filter\Menu\MenuFilter;
use Wucdbm\Bundle\MenuBuilderBundle\Form\Menu\MenuFilterType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\CreateType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\MenuItemType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\RouteChoiceType;
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

            $route = $this->getParameter('wucdbm_menu_builder_client.order_route');

            return $this->redirectToRoute($route, [
                'id' => $menu->getId()
            ]);
        }

        $data = [
            'form' => $form->createView(),
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/create.html.twig', $data);
    }

    public function nestableAction($id) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);
        
        $form = $this->createForm(CreateType::class, $menu);

        $data = [
            'menu' => $menu,
            'form' => $form->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/nestable.html.twig', $data);
    }

    public function refreshNestableAction(Menu $menu) {
        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/nestable/nestable.html.twig', $data);
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
            $manager->orderNestable($menu, $order);

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

    public function sortableAction($id) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);
        
        $form = $this->createForm(CreateType::class, $menu);

        $data = [
            'menu' => $menu,
            'form' => $form->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/sortable.html.twig', $data);
    }

    public function refreshSortableAction(Menu $menu) {
        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/sortable/sortable.html.twig', $data);
    }

    public function updateSortableAction(Menu $menu, Request $request) {
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
            $manager->orderSortable($menu, $order);

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

    public function addItemAction(Menu $menu, Request $request) {
        $item = new MenuItem();
        $form = $this->createForm(RouteChoiceType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->redirectToRoute('wucdbm_menu_builder_client_menu_items_add_by_route', [
                'id'      => $menu->getId(),
                'routeId' => $item->getRoute()->getId()
            ]);
        }

        $data = [
            'menu' => $menu,
            'form' => $form->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/add_item.html.twig', $data);
    }

    public function addItemByRouteAction(Menu $menu, $routeId, Request $request) {
        $routeRepository = $this->container->get('wucdbm_menu_builder.repo.routes');
        $route = $routeRepository->findOneById($routeId);
        $item = new MenuItem();
        $item->setRoute($route);
        $item->setMenu($menu);
        $menu->addItem($item);

        return $this->editItem($item, $request);
    }

    public function addSubItemAction(Menu $menu, $itemId, Request $request) {
        $item = new MenuItem();
        $form = $this->createForm(RouteChoiceType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->redirectToRoute('wucdbm_menu_builder_client_menu_item_add_sub_by_route', [
                'id'      => $menu->getId(),
                'itemId'  => $itemId,
                'routeId' => $item->getRoute()->getId()
            ]);
        }

        $data = [
            'menu' => $menu,
            'form' => $form->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/add_item.html.twig', $data);
    }

    public function addSubItemByRouteAction(Menu $menu, $itemId, $routeId, Request $request) {
        $routeRepository = $this->container->get('wucdbm_menu_builder.repo.routes');
        $menuItemRepository = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $parent = $menuItemRepository->findOneById($itemId);
        $route = $routeRepository->findOneById($routeId);
        $item = new MenuItem();
        $item->setRoute($route);
        $item->setMenu($menu);
        $item->setParent($parent);
        $parent->addChild($item);
        $menu->addItem($item);

        return $this->editItem($item, $request);
    }

    public function editItemAction($id, $itemId, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $item = $repo->findOneById($itemId);

        return $this->editItem($item, $request);
    }

    public function editItem(MenuItem $item, Request $request) {
        $form = $this->createForm(MenuItemType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repo = $this->container->get('wucdbm_menu_builder.repo.menus_items');
            $repo->save($item);

            $route = $this->container->getParameter('wucdbm_menu_builder_client.order_route');
            
            return $this->redirectToRoute($route, [
                'id' => $item->getMenu()->getId()
            ]);
        }

        $data = [
            'menu'  => $item->getMenu(),
            'route' => $item->getRoute(),
            'item'  => $item,
            'form'  => $form->createView()
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/add_item_by_route.html.twig', $data);
    }

    public function removeAction(Menu $menu, Request $request) {
        if ($request->isXmlHttpRequest()) {
            if ($menu->getIsSystem()) {
                return $this->json([
                    'witter' => [
                        'title' => sprintf('Failed removing %s', $menu->getName()),
                        'text'  => sprintf('You can not delete "%s" because it is a System menu and doing so will break the application.', $menu->getName())
                    ]
                ]);
            }

            $isConfirmed = $request->request->get('is_confirmed');

            if ($isConfirmed) {
                $menuRepository = $this->container->get('wucdbm_menu_builder.repo.menus');
                $menuRepository->remove($menu);

                return $this->json([
                    'redirect' => $this->generateUrl('wucdbm_menu_builder_client_menu_list')
                ]);
            }

            return $this->json([
                'witter' => [
                    'title' => 'You must confirm this action',
                    'text'  => 'You must confirm first in order to delete this Menu'
                ]
            ]);
        }

        $referer = $request->headers->get('Referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('wucdbm_menu_builder_client_menu_list');
    }

}