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

use Piwik\Http;

class SendStats
{
    private const TIMEOUT = 30;

    protected $config;
    protected $payload;
    protected $endpoint;

    public function __construct(array $config, string $payload, string $endpoint)
    {
        $this->config = $config;
        $this->payload = $payload;
        $this->endpoint = $endpoint;
    }

    /**
     * @return array{response: string, status: int}
     * @throws \Exception when the server cannot be reached
     */
    public function ship()
    {
        $response = Http::sendHttpRequestBy(
            Http::getTransportMethod(),
            $this->config['url'] . $this->endpoint,
            self::TIMEOUT,
            null,
            null,
            null,
            0,
            false,
            false,
            false,
            true,
            'POST',
            null,
            null,
            $this->payload,
            ['x-api-key: ' . $this->config['key']]
        );

        return [
            'response' => (string) ($response['data'] ?? ''),
            'status' => (int) ($response['status'] ?? 0),
        ];
    }
}
