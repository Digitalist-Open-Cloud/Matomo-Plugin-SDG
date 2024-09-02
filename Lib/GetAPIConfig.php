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
use Piwik\Plugins\SDG\SystemSettings;

class GetAPIConfig
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get()
    {
        $apiUrl = 'https://collect.youreurope.europa.eu';
        $settings = new SystemSettings();
        $env = $settings->environment->getValue();
        if ($env[0] == 'acceptance') {
            $apiUrl = 'https://collect.sdgacceptance.eu';
        }
        $day = (int)1;
        $day = $settings->senddate->getValue();

        $settings = new MeasurableSettings((int) $this->config['idsite']);
        $apiKey = $settings->apiKey->getValue();
        if ($env[0] == 'acceptance') {
            $apiKey = $settings->acceptanceApiKey->getValue();
        }
        return [
            'idsite' => (int)$this->config['idsite'],
            'key' => (string)$apiKey,
            'url' => (string)$apiUrl,
            'day' => (int)$day,
        ];
    }
}
