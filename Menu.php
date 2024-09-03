<?php

/**
 * The SDG Single Digital Gateway plugin for Matomo.
 *
 * Copyright (C) 2024 Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Piwik\Plugins\SDG;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::isUserHasSomeAdminAccess()) {
            $menu->registerMenuIcon('SDG_SingleDigitalGateway', 'icon-code');
            $menu->addItem('SDG_SingleDigitalGateway', null, $this->urlForDefaultAction(), $order = 96);
            $menu->addItem('SDG_SingleDigitalGateway', 'SDG_SendStatus', $this->urlForDefaultAction(), $order = 97);
            $menu->addItem('SDG_SingleDigitalGateway', 'SDG_DocsHeading', $this->urlForAction('docs'), $order = 98);
            $menu->addItem('SDG_SingleDigitalGateway', 'SDG_Support', $this->urlForAction('professional'), $order = 99);
        }
    }
}
