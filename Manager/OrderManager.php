<?php

/*
 * This file is part of the MenuBuilderClientBundle package.
 *
 * (c) Martin Kirilov <wucdbm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wucdbm\Bundle\MenuBuilderClientBundle\Manager;

use Wucdbm\Bundle\MenuBuilderBundle\Entity\Menu;
use Wucdbm\Bundle\MenuBuilderBundle\Entity\MenuItem;
use Wucdbm\Bundle\MenuBuilderBundle\Manager\Manager;
use Wucdbm\Bundle\MenuBuilderBundle\Manager\MenuManager;

class OrderManager extends Manager {

    protected $manager;

    public function __construct(MenuManager $manager) {
        $this->manager = $manager;
    }

    public function orderNestable(Menu $menu, array $array) {
        $map = [];
        /** @var MenuItem $item */
        foreach ($menu->getItems() as $item) {
            $map[$item->getId()] = $item;
        }

        foreach ($array as $ord => $order) {
            $this->_orderNestable($map, $order, $ord);
        }

        $this->manager->save($menu);
    }

    protected function _orderNestable($map, $order, $ord, MenuItem $parent = null) {
        /** @var MenuItem $item */
        $item = $map[$order['id']];
        $item->setParent($parent);
        $item->setOrd($ord);
        if (isset($order['children'])) {
            /** @var MenuItem $child */
            foreach ($order['children'] as $ord => $child) {
                $this->_orderNestable($map, $child, $ord, $item);
            }
        }
    }

    public function orderSortable(Menu $menu, array $array) {
        $map = [];
        /** @var MenuItem $item */
        foreach ($menu->getItems() as $item) {
            $map[$item->getId()] = $item;
        }

        $this->_orderSortable($map, $array);

        $this->manager->save($menu);
    }

    protected function _orderSortable($map, $order, $ord = null, MenuItem $parent = null) {
        if (isset($order['id'])) {
            /** @var MenuItem $item */
            $item = $map[$order['id']];
            $item->setParent($parent);
            $item->setOrd($ord);
            if (isset($order['children'])) {
                /** @var MenuItem $child */
                foreach ($order['children'] as $ord => $child) {
                    $this->_orderSortable($map, $child, $ord, $item);
                }
            }

            return;
        }
        foreach ($order as $ord => $child) {
            $this->_orderSortable($map, $child, $ord, $parent);
        }
    }

}