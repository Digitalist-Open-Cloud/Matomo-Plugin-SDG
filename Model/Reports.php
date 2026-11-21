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

namespace Piwik\Plugins\SDG\Model;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\SDG\Service\Sql;

class Reports
{
    /**
     * @return array<array<string, mixed>> all transfers that were recorded
     */
    public function getSentReports(): array
    {
        return Db::fetchAll("SELECT * FROM " . Common::prefixTable(Sql::TABLE_NAME));
    }

    /**
     * @return array|null the transfer with this ID, or null when there is none
     */
    public function getSentReport(string $uniqueId): ?array
    {
        $report = Db::fetchRow("SELECT * FROM " . Common::prefixTable(Sql::TABLE_NAME) . " WHERE uniqueid = ?", [$uniqueId]);

        return empty($report) ? null : $report;
    }

    /**
     * @param int $idSite
     *
     * @return array|bool
     */
    public function getMostRecentSuccess(int $idSite)
    {
        $table = Common::prefixTable(Sql::TABLE_NAME);
        $query = "SELECT MAX(todate) as endDate
            FROM $table
            WHERE reportType = 'information-services'
            AND idSite = ?
            AND status = 200";

        return Db::fetchRow($query, [$idSite]);
    }

    /**
     * @return bool whether a transfer for the period that ends on this date was sent on this day
     */
    public function wasTriedOn(int $idSite, string $endDate, string $day): bool
    {
        $query = "SELECT 1 FROM " . Common::prefixTable(Sql::TABLE_NAME) . "
            WHERE reportType = 'information-services'
            AND idSite = ?
            AND DATE(todate) = ?
            AND DATE(lastChange) = ?";

        return (bool) Db::fetchOne($query, [$idSite, $endDate, $day]);
    }
}
