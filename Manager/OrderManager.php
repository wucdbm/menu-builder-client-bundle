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

    public function order(Menu $menu, array $order) {
        $map = [];
        /** @var MenuItem $item */
        foreach ($menu->getItems() as $item) {
            $map[$item->getId()] = $item;
        }

        foreach ($order as $ord => $firstLevel) {
            $this->_order($map, $firstLevel, $ord);
        }

        $this->manager->save($menu);
    }

    protected function _order($map, $order, $ord, MenuItem $parent = null) {
        /** @var MenuItem $item */
        $item = $map[$order['id']];
        $item->setParent($parent);
        $item->setOrd($ord);
        if (isset($order['children'])) {
            /** @var MenuItem $child */
            foreach ($order['children'] as $ord => $child) {
                $this->_order($map, $child, $ord, $item);
            }
        }
    }

}