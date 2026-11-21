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

namespace Piwik\Plugins\SDG\Service;

use Exception;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\SDG\Lib\GetAPIConfig;
use Piwik\Plugins\SDG\Lib\GetStatisticsOnInformationServices;
use Piwik\Plugins\SDG\Lib\SendStats;
use Piwik\Plugins\SDG\Lib\SetUUID;
use Piwik\Plugins\SDG\Model\Period;
use Piwik\Plugins\SDG\Model\StatisticsPayload;

class SOIS
{
    protected $config;
    protected $from;
    protected $to;
    protected $send;
    protected $uuid;

    public function __construct($config, $from, $to, $send = false, $uuid = null)
    {
        $this->config = $config;
        $this->from = $from;
        $this->to = $to;
        $this->send = $send;
        $this->uuid = $uuid;
    }

    public function send()
    {
        $api = new GetApiConfig($this->config);
        $apiconfig = $api->get();
        # Get UUID from youreurope.
        if (!isset($this->uuid)) {
            $getUuid = new SetUUID($apiconfig);
            $uuid = $getUuid->get();
        } else {
            $uuid = $this->uuid;
        }
        $date_sent = Date::now()->getDatetime();
        $all = new GetStatisticsOnInformationServices($apiconfig['idsite']);
        // fetch all the data.
        $result = $all->getSources($this->from, $this->to);
        // format the data.
        $referencePeriod = new Period($this->from, $this->to);
        $payload = json_encode(new StatisticsPayload($uuid, $referencePeriod, $result), JSON_UNESCAPED_SLASHES);
        if ($this->send !== true) {
            return [
                'status' => 'dump',
                'payload' => $payload,
            ];
        }

        $status = 0;
        $nbEntries = 0;
        try {
            $sent = (new SendStats($apiconfig, $payload, '/v2/statistics/information-services'))->ship();
            $status = $sent['status'];
            if ($status === 200) {
                $nbEntries = (int) json_decode($payload)->nbEntries;
            }
        } catch (Exception $e) {
            StaticContainer::get(LoggerInterface::class)->error('SDG: the statistics could not be sent: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        (new Sql())->insertRequestInfo(
            $uuid,
            'information-services',
            $apiconfig['idsite'],
            $this->from,
            $this->to,
            // The status page shows a transfer that got no answer as 404.
            $status === 0 ? 404 : $status,
            $nbEntries,
            $date_sent
        );

        return [
            'status' => $status,
            'payload' => $payload,
        ];
    }
}
