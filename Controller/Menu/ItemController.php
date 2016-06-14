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
use Wucdbm\Bundle\MenuBuilderBundle\Entity\Menu;
use Wucdbm\Bundle\WucdbmBundle\Controller\BaseController;

class ItemController extends BaseController {

    public function removeItemAction(Menu $menu, $itemId, Request $request) {
        $menuItemRepository = $this->container->get('wucdbm_menu_builder.repo.menus_items');
        $item = $menuItemRepository->findOneById($itemId);
        $menuItemRepository->remove($item);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'refresh' => true
            ]);
        }

        return $this->redirectToRoute('wucdbm_menu_builder_client_menu_nestable', [
            'id' => $menu->getId()
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