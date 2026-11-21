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
use Piwik\Date;
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

    /**
     * Sends the previous month for every website with report URLs, from the send date on until
     * the portal has accepted it. A website is tried once a day.
     */
    public function sendInformationServicesData()
    {
        try {
            $today = Date::now();
            $settings = new SystemSettings();
            // A send date after the last day of the month is that last day.
            $sendDay = min((int) $settings->senddate->getValue(), (int) $today->toString('t'));
            if ((int) $today->toString('j') < $sendDay) {
                return;
            }

            $endDate = Date::factory($today->toString('Y-m-01'))->subDay(1)->toString('Y-m-d');
            $startDate = substr($endDate, 0, 8) . '01';

            $this->logger->debug('Running schedule for SDG');
            $reports = new Reports();
            foreach ((new GetAllUrls())->allSitesId() as $id) {
                $succesful = $reports->getMostRecentSuccess($id);
                $lastEndDate = empty($succesful['endDate']) ? '' : date('Y-m-d', strtotime($succesful['endDate']));
                if ($lastEndDate === $endDate || $reports->wasTriedOn((int) $id, $endDate, $today->toString('Y-m-d'))) {
                    continue;
                }

                $sois = new SOIS(['idsite' => $id], $startDate, $endDate, true);
                $result = $sois->send();
                if ($result['status'] === 200) {
                    $this->logger->info('Statistics data sent for SDG');
                } else {
                    $this->logger->error('Statistics data not sent for SDG');
                }
            }
        } catch (Exception $e) {
            // this makes Matomo try to retry at least three times.
            throw new RetryableException($e->getMessage());
        }
    }
}
