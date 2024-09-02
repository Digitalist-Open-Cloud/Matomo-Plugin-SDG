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

namespace Piwik\Plugins\SDG\Lib;

use Piwik\Plugins\SDG\MeasurableSettings;
use Piwik\Plugins\SitesManager\API as APISitesManager;

class GetAllUrls
{
    public function list()
    {
        $urlstring = (string)'';
        $urls = (array)[];

        $ids = $this->allSitesId();
        foreach ($ids as $id) {
            $settings = new MeasurableSettings((int) $id);
            $urlValue = $settings->reportUrls->getValue();
            $urlValue = preg_replace('/\s+/', '', $urlValue);
            $urls[] = $urlValue;
        }
        /**
         * We need to filter the urls and in the end return
         * as "my.url|myother.url"
         */
        $clean_urls = array_filter($urls);
        foreach ($clean_urls as $url) {
            $urlstring .= $url . '|';
        }
        $urls = str_replace(",", "|", trim($urlstring));
        $urls = preg_replace('/\|$/', '', $urls);
        return $urls;
    }

    /**
     * Returns all sites id, which has a value of report url.
     */
    public function allSitesId()
    {
        $sites = (array)[];
        $list = APISitesManager::getInstance()->getAllSitesId();
        foreach ($list as $id) {
            $settings = new MeasurableSettings((int) $id);
            $urlValue = $settings->reportUrls->getValue();
            if (strlen($urlValue >= 1)) {
                $sites[] = $id;
            }
        }
        return $sites;
    }
}
