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

namespace Piwik\Plugins\SDG\Service;

use Piwik\Common;
use Piwik\Db;

/**
 * Service for handling the plugins extended database.
 */
class Sql
{
    public const TABLE_NAME = "sdg";
    public const UNIQUEID = "uniqueid";
    public const REPORTTYPE = "reportType";
    public const IDSITE = "idSite";
    public const FROMDATE = "fromdate";
    public const TODATE = "todate";
    public const STATUS = "status";
    public const NBENTRIES = "nbEntries";
    public const LASTCHANGE = "lastChange";
    public const FIELDS = [
        self::UNIQUEID,
        self::REPORTTYPE,
        self::IDSITE,
        self::FROMDATE,
        self::TODATE,
        self::STATUS,
        self::NBENTRIES,
        self::LASTCHANGE,
    ];

    public function sent(int $limit = 10): array
    {
        $sql = "SELECT " . implode(", ", Sql::FIELDS) . " FROM " . Common::prefixTable(self::TABLE_NAME) .
        " ORDER BY fromdate DESC LIMIT " . $limit;
        return Db::fetchAll($sql);
    }

    public function insertRequestInfo($uniqueid, $type, $idSite, $fromdate, $todate, $status, $nbEntries, $date)
    {
        $sql =  "INSERT INTO " . Common::prefixTable(self::TABLE_NAME) . "( " . implode(", ", Sql::FIELDS) . " )
            VALUES (
            '"  . $uniqueid . "' ,
            '"  . $type . "' ,
            '"  . $idSite . "' ,
            '"  . $fromdate . "' ,
            '"  . $todate . "' ,
            '"  . $status . "' ,
            '"  . $nbEntries . "' ,
            '"  . $date . "' )";


        Db::query($sql);
    }
}
