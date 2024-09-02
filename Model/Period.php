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

class Period
{
    public const DATE_FORMAT = "Y-m-d\TH:i:s.00\Z";
    /**
     * @var string
     */
    public $startDate;

    /**
     * @var string
     */
    public $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = date_format(date_create($startDate), self::DATE_FORMAT);
        $this->endDate =  date_format(date_create($endDate), self::DATE_FORMAT);
    }
}
