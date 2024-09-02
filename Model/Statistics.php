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

class Statistics
{
    public const DESKTOP = "PC";
    public const SMARTPHONE = "Smartphone";
    public const TABLET = "Tablet";
    public const OTHER_DEVICE = "Others";

    public const DEVICES = [0 => self::DESKTOP, 1 => self::SMARTPHONE, 2 => self::TABLET];

    /**
     * @var int
     */
    public $nbVisits;

    /**
     * @var string
     */
    public $originatingCountry;

    /**
     * @var string
     */
    public $deviceType;

    protected static $isoToEurostat = [
        "GR" => "EL",
    ];

    public function __construct(int $nbVisits, string $originatingCountry, $deviceType)
    {
        $this->nbVisits = $nbVisits;
        $this->originatingCountry = $this->convertOriginatingCountry($originatingCountry);
        $this->deviceType = $this->convertDeviceType($deviceType);
    }

    protected static function convertOriginatingCountry($country)
    {
        $upperCountry = strtoupper($country);
        if (key_exists($upperCountry, self::$isoToEurostat)) {
            return self::$isoToEurostat[$upperCountry];
        } else {
            return $upperCountry;
        }
    }

    private function convertDeviceType($deviceType)
    {
        if (key_exists($deviceType, self::DEVICES)) {
            return self::DEVICES[$deviceType];
        } else {
            return self::OTHER_DEVICE;
        }
    }
}
