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

use Piwik\Archive;
use Piwik\Metrics;
use Piwik\Plugins\SDG\Archiver;
use Piwik\Plugins\SDG\Model\Source;
use Piwik\Plugins\SDG\Model\Statistics;

class GetStatisticsOnInformationServices
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

   /**
     * Get archived statistics from db
     *
     * @return Source[]
     */
    public function getSources(string $periodStart, string $periodEnd)
    {
        $siteId = $this->id;
        $dateString = substr($periodStart, 0, 10) . "," . substr($periodEnd, 0, 10);
        $period = 'range';
        $archive = Archive::build($siteId, $period, $dateString, null);
        $statisticsArchiveData = $archive->getDataTable(Archiver::SDG_STATITICS);
        $sources = [];
        foreach ($statisticsArchiveData->getRowsWithoutSummaryRow() as $row) {
                $pageUrl = $row->getMetadata(Archiver::PAGE_URL_INDEX);
            if (!key_exists($pageUrl, $sources)) {
                $sources[$pageUrl] = new Source($pageUrl);
            }
                $statistics = new Statistics(
                    $row->getColumn(Metrics::INDEX_NB_VISITS),
                    $row->getMetadata(Archiver::COUNTRY_CODE_INDEX),
                    $row->getMetadata(Archiver::DEVICE_TYPE_INDEX)
                );
                $sources[$pageUrl]->addStatistics($statistics);
        }
        return array_values($sources);
    }
}
