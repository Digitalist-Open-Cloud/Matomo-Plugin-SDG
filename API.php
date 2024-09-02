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

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\DataTable\Map;
use Piwik\Metrics;
use Piwik\Plugin\API as PiwikAPI;

/**
 * API for plugin SDGWebStatistics
 *
 * @method static \Piwik\Plugins\SDGWebStatistics\API getInstance()
 */
class API extends PiwikAPI
{
    /**
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getSingleDigitalGateway($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $statisticsArchiveData = $archive->getDataTable(Archiver::SDG_STATITICS);
        $statisticsDataTables = [];
        if ($statisticsArchiveData instanceof Map) {
            $statisticsDataTables = $statisticsArchiveData->getDataTables();
        } else {
            $statisticsDataTables[] = $statisticsArchiveData;
        }

        $dataTable = new DataTable();
        foreach ($statisticsDataTables as $statisticsDataTable) {
            foreach ($statisticsDataTable->getRows() as $row) {
                    $dataTable->addRowFromSimpleArray([
                        'url' => $row->getMetadata(Archiver::PAGE_URL_INDEX),
                        'pageTitle' => $row->getMetadata(Archiver::PAGE_TITLE_INDEX),
                        'country' => $row->getMetadata(Archiver::COUNTRY_CODE_INDEX),
                        'deviceType' => $row->getMetadata(Archiver::DEVICE_TYPE_INDEX),
                        'nb_visits' => (string)$row->getColumn(Metrics::INDEX_NB_VISITS),
                    ]);
            }
        }

        $dataTable->queueFilter(
            'ColumnCallbackReplace',
            ['country', 'Piwik\Plugins\UserCountry\countryTranslate']
        );
        $dataTable->queueFilter(
            'ColumnCallbackReplace',
            ['deviceType', 'Piwik\Plugins\DevicesDetection\getDeviceTypeLabel']
        );
        return $dataTable;
    }
}
