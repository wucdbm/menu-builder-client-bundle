<?php

/*
 * This file is part of the MenuBuilderClientBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Controller\Menu;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item\MenuItemType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item\RouteChoiceType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\Item\ExternalUrlItemType;
use Wucdbm\Bundle\WucdbmBundle\Controller\BaseController;

class ItemController extends BaseController {

    public function chooseRouteAction($id, $parentId, Request $request) {
        $manager = $this->container->get('wucdbm_menu_builder.manager.menus');
        $menu = $manager->findOneById($id);
        $item = $manager->createItem();

        $activeForm = 'route';

        $routeForm = $this->createForm(RouteChoiceType::class, $item);

        $routeForm->handleRequest($request);

        if ($routeForm->isValid()) {
            return $this->redirectToRoute('wucdbm_menu_builder_client_menu_item_add', [
                'id'       => $menu->getId(),
                'routeId'  => $item->getRoute()->getId(),
                'parentId' => $parentId
            ]);
        }

        $urlForm = $this->createForm(ExternalUrlItemType::class, $item);

        $urlForm->handleRequest($request);

        if ($urlForm->isValid()) {
            $item->setMenu($menu);
            $menu->addItem($item);

            if ($parentId) {
                $menuItemRepository = $this->container->get('wucdbm_menu_builder.repo.menus_items');
                $parent = $menuItemRepository->findOneById($parentId);
                $item->setParent($parent);
                $parent->addChild($item);
            }

            return $this->editCreateItemSuccess($item, $request);
        }

        if ($routeForm->isSubmitted()) {
            $activeForm = 'route';
        } elseif ($urlForm->isSubmitted()) {
            $activeForm = 'url';
        }

        $data = [
            'menu'       => $menu,
            'parentId'   => $parentId,
            'routeForm'  => $routeForm->createView(),
            'urlForm'    => $urlForm->createView(),
            'activeForm' => $activeForm,
            'action'     => $this->generateUrl('wucdbm_menu_builder_client_menu_item_choose_route', [
                'id'       => $id,
                'parentId' => $parentId
            ])
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@WucdbmMenuBuilderClient/Menu/Item/route_choice/choose_popup.html.twig', $data)
            ]);
        }

        return $this->render('@WucdbmMenuBuilderClient/Menu/Item/route_choice/choose.html.twig', $data);
    }

    public function addItemAction($id, $routeId, $parentId, Request $request) {
        $manager = $this->container->get('wucdbm_menu_builder.manager.menus');
        $item = $manager->createItem();
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);
        $routeRepository = $this->container->get('wucdbm_menu_builder.repo.routes');
        $route = $routeRepository->findOneById($routeId);
        $item->setRoute($route);
        $item->setMenu($menu);
        $menu->addItem($item);

        if ($parentId) {
            $menuItemRepository = $this->container->get('wucdbm_menu_builder.repo.menus_items');
            $parent = $menuItemRepository->findOneById($parentId);
            $item->setParent($parent);
            $parent->addChild($item);
        }

        return $this->editCreateItem($item, $this->generateUrl('wucdbm_menu_builder_client_menu_item_add', [
            'id'       => $menu->getId(),
            'routeId'  => $routeId,
            'parentId' => $parentId
        ]), $request);
    }

    public function editItemAction($id, $itemId, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $item = $repo->findOneById($itemId);

        return $this->editCreateItem($item, $this->generateUrl('wucdbm_menu_builder_client_menu_item_edit', [
            'id'     => $id,
            'itemId' => $itemId
        ]), $request);
    }

    protected function editCreateItem(MenuItem $item, $action, Request $request) {
        $form = $this->createForm(MenuItemType::class, $item);

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->editCreateItemSuccess($item, $request);
        }

        $data = [
            'menu'   => $item->getMenu(),
            'route'  => $item->getRoute(),
            'item'   => $item,
            'parent' => $item->getParent(),
            'form'   => $form->createView(),
            'action' => $action
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@WucdbmMenuBuilderClient/Menu/Item/create/create_popup.html.twig', $data)
            ]);
        }

        return $this->render('@WucdbmMenuBuilderClient/Menu/Item/create/create.html.twig', $data);
    }

    protected function editCreateItemSuccess(MenuItem $item, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $repo->save($item);

        $route = $this->container->getParameter('wucdbm_menu_builder_client.order_route');

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp'         => $this->renderView('@WucdbmMenuBuilderClient/Menu/Item/create/success_popup.html.twig', [
                    'item' => $item
                ]),
                'refreshMenu' => true
            ]);
        }

        return $this->redirectToRoute($route, [
            'id' => $item->getMenu()->getId()
        ]);
    }

    public function updateItemNameAction($id, $itemId, Request $request) {
        $post = $request->request;
        // name, value, pk
        $name = $post->get('value', null);

        if (null === $name) {
            return new Response('Error - Empty value', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repo = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $item = $repo->findOneById($itemId);
        $item->setName($name);
        $repo->save($item);

        return new Response();
    }

}