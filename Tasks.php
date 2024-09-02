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

use Exception;
use Piwik\Plugins\SDG\Lib\GetAllUrls;
use Piwik\Plugins\SDG\Model\Reports;
use Piwik\Plugins\SDG\Service\SOIS;
use Piwik\Scheduler\RetryableException;

class Tasks extends \Piwik\Plugin\Tasks
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function schedule()
    {
        $this->logger->debug('Running schedule for SDG');
        $this->hourly("sendInformationServicesData", null, self::LOWEST_PRIORITY);
    }

    public function sendInformationServicesData()
    {
        try {
            $today = date("d");
            $startDate = date('Y-m-d', strtotime("first day of -1 month"));
            $endDate = date('Y-m-d', strtotime("last day of -1 month"));
            $settings = new SystemSettings();
            $day = $settings->senddate->getValue();
            if ($today == $day) {
                $this->logger->debug('Running schedule for SDG');
                $ids = new GetAllUrls();
                $getids = $ids->allSitesId();
                foreach ($getids as $id) {
                    $latest = new Reports();
                    $succesful = $latest->getMostRecentSuccess($id);
                    $lastEndDate = date('Y-m-d', strtotime($succesful['endDate']));
                    // If lastEndDate is not the same as enddate - try send report.
                    if ($lastEndDate !== $endDate) {
                        $config['idsite'] = $id;
                        $sois = new SOIS($config, $startDate, $endDate, true);
                        $result = $sois->send();
                        if ($result['status'] == '200') {
                            $this->logger->info('Statistics data sent for SDG');
                        } else {
                            $this->logger->error('Statistics data not sent for SDG');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // this makes Matomo try to retry at least three times.
            throw new RetryableException($e->getMessage());
        }
    }
}
