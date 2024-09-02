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

namespace Piwik\Plugins\SDG\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\SDG\Lib\GetAllUrls;

/**
 * GetSDGurls
 */
class GetSDGurls extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('sdg:get-urls');
        $this->setDescription('All SDG urls.');
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $all = new GetAllUrls();
        $urls = $all->list();
        $output->writeln("All Urls: $urls");
        return self::SUCCESS;
    }
}
