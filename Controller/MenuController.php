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
use Wucdbm\Bundle\MenuBuilderBundle\Filter\Menu\MenuFilter;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\CreateType;
use Wucdbm\Bundle\MenuBuilderClientBundle\Form\Menu\FilterType;
use Wucdbm\Bundle\WucdbmBundle\Controller\BaseController;

class MenuController extends BaseController {

    public function listAction(Request $request) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $filter = new MenuFilter();
        $pagination = $filter->getPagination()->enable();
        $filterForm = $this->createForm(FilterType::class, $filter);
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

    public function refreshListRowAction($id) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

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
        $manager = $this->container->get('wucdbm_menu_builder.manager.menus');
        $menu = $manager->create();
        $form = $this->createForm(CreateType::class, $menu);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $manager->save($menu);

            $route = $this->getParameter('wucdbm_menu_builder_client.order_route');

            $url = $this->generateUrl($route, [
                'id' => $menu->getId()
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'redirect' => $url
                ]);
            }

            return $this->redirect($url);
        }

        $data = [
            'form' => $form->createView(),
            'menu' => $menu
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'mfp' => $this->renderView('@WucdbmMenuBuilderClient/Menu/create/create_popup.html.twig', $data)
            ]);
        }

        return $this->render('@WucdbmMenuBuilderClient/Menu/create/create.html.twig', $data);
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

    public function refreshNestableAction($id) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

        $data = [
            'menu' => $menu
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/nestable/nestable.html.twig', $data);
    }

    public function updateNestableAction($id, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

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

    public function sortableAction($id, $class) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

        $form = $this->createForm(CreateType::class, $menu);

        $data = [
            'menu'  => $menu,
            'form'  => $form->createView(),
            'class' => $class
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/sortable.html.twig', $data);
    }

    public function refreshSortableAction($id) {
        $repo = $this->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

        $data = [
            'menu'  => $menu,
            'class' => 'vertical-simple'
        ];

        return $this->render('@WucdbmMenuBuilderClient/Menu/sortable/sortable.html.twig', $data);
    }

    public function updateSortableAction($id, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

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

    public function removeAction($id, Request $request) {
        $repo = $this->container->get('wucdbm_menu_builder.repo.menus');
        $menu = $repo->findOneById($id);

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

    public function removeItemAction(Request $request) {
        $id = $request->request->get('id');
        $itemId = $request->request->get('itemId');
        $isFull = intval($request->request->get('isFull'));

        if (!$id || !$itemId || !is_numeric($isFull)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'witter'  => [
                        'title' => 'Error: id, itemId or isFull is not set',
                        'text'  => sprintf('%s, %s, %s', $id, $itemId, $isFull)
                    ]
                ]);
            }

            if ($id) {
                $route = $this->container->getParameter('wucdbm_menu_builder_client.order_route');

                return $this->redirectToRoute($route, [
                    'id' => $id
                ]);
            }

            return $this->redirectToRoute('wucdbm_menu_builder_client_menu_list');
        }

        $menuItemRepository = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $item = $menuItemRepository->findOneById($itemId);

        if (!$item) {
            return $this->json([
                'success' => false,
                'witter'  => [
                    'text' => 'Link not found'
                ]
            ]);
        }

        $menuItemRepository->remove($item, $isFull);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'refresh' => true
            ]);
        }

        $route = $this->container->getParameter('wucdbm_menu_builder_client.order_route');

        return $this->redirectToRoute($route, [
            'id' => $id
        ]);
    }

}