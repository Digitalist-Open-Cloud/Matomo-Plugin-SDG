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

declare(strict_types=1);

namespace Piwik\Plugins\SDG\Lib;

use Exception;

class SetUUID
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get()
    {
        $url = $this->config['url'] . '/v1/unique-id';
        $curl = curl_init((string)$url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        $key = $this->config['key'];
        $headers = ["x-api-key: $key"];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new Exception(curl_error($curl));
        }
        curl_close($curl);
        $resp = json_decode($resp);

        return $resp;
    }
}
