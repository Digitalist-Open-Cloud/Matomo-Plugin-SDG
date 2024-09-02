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

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\DbHelper;
use Piwik\Metrics;
use Piwik\Plugin\Archiver as CoreArchiver;
use Piwik\Plugins\SDG\Lib\GetAllUrls;
use Piwik\Tracker\Action;

class Archiver extends CoreArchiver
{
    protected const LOG_ACTIONS = "log_link_visit_action";
    protected const ACTIONS = "log_action";
    protected const VISITS = "log_visit";
    protected const SERVER_TIME = "server_time";
    protected const COUNTRY = "location_country";
    protected const DEVICE_TYPE = "config_device_type";
    public const SDG_STATITICS = "SDG_reports";

    //Metadata indexes for archive
    public const PAGE_URL_INDEX = "1";
    public const PAGE_TITLE_INDEX = "2";
    public const COUNTRY_CODE_INDEX = "3";
    public const DEVICE_TYPE_INDEX = "4";

    public function aggregateDayReport()
    {
        $statisticsDataTable = $this->aggregateStatistics();
        $this->getProcessor()->insertBlobRecord(self::SDG_STATITICS, $statisticsDataTable->getSerialized());
    }

    public function aggregateMultipleReports()
    {
        $this->getProcessor()->aggregateDataTableRecords([self::SDG_STATITICS]);
    }

    private function aggregateStatistics()
    {
        $logAggregator = $this->getLogAggregator();
        $from = [(self::LOG_ACTIONS)];
        $from[] = [
            "table" => self::ACTIONS,
            "tableAlias" => "urls",
            "joinOn" => self::LOG_ACTIONS . ".idaction_url=urls.idaction",
        ];
        $from[] = [
            "table" => self::ACTIONS,
            "tableAlias" => "titles",
            "joinOn" => self::LOG_ACTIONS . ".idaction_name=titles.idaction",
        ];
        $from[] = [
            "table" => self::VISITS,
            "tableAlias" => "visits",
            "joinOn" => self::LOG_ACTIONS . ".idvisit=visits.idvisit",
        ];
        // Get all SDG report Urls, so we just archive the visists with the url present.
        $all = new GetAllUrls();
        $urls = $all->list();
        $where = "titles.type=" . Action::TYPE_PAGE_TITLE .
          " AND urls.type=" . Action::TYPE_PAGE_URL .
          " AND urls.url_prefix!=0" . " AND urls.name REGEXP '$urls'";
        $where = $logAggregator->getWhereStatement(self::LOG_ACTIONS, self::SERVER_TIME, $where);
        $select = "urls.name as pageUrl
            , titles.name as pageTitle
            , visits." . self::COUNTRY . " as countryCode
            , visits." . self::DEVICE_TYPE . " as deviceType
            , count(distinct visits.idvisit) as nb_visits";
        $groupBy = "urls.name, titles.name, visits." . self::COUNTRY . ", visits." . self::DEVICE_TYPE;
        $query = $logAggregator->generateQuery($select, $from, $where, $groupBy, "");
        $query['sql'] = DbHelper::addMaxExecutionTimeHintToQuery($query['sql'], -1);
        $result = $logAggregator->getDb()->query($query["sql"], $query["bind"]);
        $dataTable = new DataTable();
        while ($row = $result->fetch()) {
            $dataTable->addRowFromArray([
                Row::COLUMNS => [
                    "label" => $row["pageUrl"] . "_" . $row["countryCode"] . "_" . $row["deviceType"],
                    Metrics::INDEX_NB_VISITS => $row["nb_visits"],
                ],
                Row::METADATA => [
                    self::PAGE_URL_INDEX => $row["pageUrl"],
                    self::PAGE_TITLE_INDEX => $row["pageTitle"],
                    self::COUNTRY_CODE_INDEX => $row["countryCode"],
                    self::DEVICE_TYPE_INDEX => $row["deviceType"],
                ],
            ]);
        }
        return $dataTable;
    }
}
