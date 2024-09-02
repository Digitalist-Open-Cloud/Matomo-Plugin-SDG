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

class StatisticsPayload
{
    public const DATE_FORMAT = "Y-m-d\TH:i:s.00\Z";

    public $uniqueId;

    /**
     * @var Period
     */
    public $referencePeriod;

    /**
     * @var string
     */
    public $transferDate;

    /**
     * @var string
     */
    public $transferType;

    /**
     * @var int
     */
    public $nbEntries;

    /**
     * @var Source[]
     */
    public $sources;

    public function __construct(
        $uniqueId,
        Period $referencePeriod,
        array $sources,
        int $nbEntries = null,
        string $transferDate = null,
        string $transferType = 'API'
    ) {
        $this->uniqueId = $uniqueId;
        $this->referencePeriod = $referencePeriod;
        $this->sources = $sources;
        $this->nbEntries = $nbEntries ?? count($this->sources);
        $this->transferDate = $transferDate ?? date_create("now")->format(self::DATE_FORMAT);
        $this->transferType = $transferType;
    }
}
