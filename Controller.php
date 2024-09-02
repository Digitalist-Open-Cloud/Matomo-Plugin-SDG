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

use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugins\SDG\Model\Reports;
use Piwik\Plugins\SDG\Service\SOIS;
use Piwik\Url;

/**
 * Class Controller
 *
 * @package Piwik\Plugins\SDG
 */
class Controller extends \Piwik\Plugin\Controller
{
    /**
     *
     * @var Reports
     */
    protected $db;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Controller constructor.
     *
     * @param Reports $db
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(Reports $db, \Psr\Log\LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     *
     * @return string
     * @throws \Exception
     */
    public function index(): string
    {
        Piwik::checkUserHasSomeAdminAccess();

        return $this->renderPage();
    }

    /**
     * @return array
     * @throws \Exception
     */
    final protected function reports(): array
    {
        $reports = [];
        $siteIds = [];
        $names = [];
        $fromDates = [];
        $toDates = [];
        $statuses = [];

        foreach ($this->db->getSentReports() as $report) {
            $resend_url = Url::getCurrentQueryStringWithParametersModified(
                [
                    'module' => 'SDG',
                    'action' => 'resend',
                    'uniqueid' => $report['uniqueid'],
                    'from' => date_format(date_create($report['fromdate']), "Y-m-d"),
                    'to' => date_format(date_create($report['todate']), "Y-m-d"),
                ]
            );

            $siteIds[] = $report['idSite'];
            $names[] = $report['reportType'];
            $fromDates[] = date('Y-m-d', strtotime($report['fromdate']));
            $toDates[] = date('Y-m-d', strtotime($report['todate']));
            $statuses[] =  $report['status'];
            $reports[] = [
                'id' => $report['id'],
                'uniqueid' => $report['uniqueid'],
                'reportType' => $report['reportType'],
                'idSite' => $report['idSite'],
                'fromdate' => date('Y-m-d', strtotime($report['fromdate'])),
                'todate' => date('Y-m-d', strtotime($report['todate'])),
                'status' => (int)$report['status'],
                'nbEntries' => $report['nbEntries'],
                'lastChange' => $report['lastChange'],
                'resend_url' => $resend_url,
            ];
        }
        return [
            $reports,
            array_unique($siteIds),
            array_unique($names),
            array_unique($fromDates),
            array_unique($toDates),
            array_unique($statuses),
        ];
    }

    /**
     *
     * @return string
     * @throws \Exception
     */
    public function resend(): string
    {
        Piwik::checkUserHasSomeAdminAccess();

        try {
            $uuid = \Piwik\Request::fromRequest()->getStringParameter('uniqueid');
            $uuid = strip_tags($uuid);
            $id = \Piwik\Request::fromRequest()->getStringParameter('idSite');
            $id = strip_tags($id);
            $from = \Piwik\Request::fromRequest()->getStringParameter('from');
            $from = strip_tags($from);
            $to = \Piwik\Request::fromRequest()->getStringParameter('to');
            $to = strip_tags($to);
            $config = [];
            $config['idsite'] = $id;
            $sois = new SOIS($config, $from, $to, true, $uuid);
            $result = $sois->send();

            $notification = new Notification(
                Piwik::translate('SDG_LogsSent')
            );
            $notification->context = Notification::CONTEXT_SUCCESS;
        } catch (\Exception $e) {
            $notification = new Notification(
                Piwik::translate('SDG_ErrorSendingLogs')
            );
            $notification->context = Notification::CONTEXT_ERROR;
        }

        return $this->renderPage([$notification]);
    }

    /**
     *
     * @param array|null $notifications
     *
     * @return string
     */
    private function renderPage(?array $notifications = null): string
    {

        [$reports, $siteIds, $names, $fromDates, $toDates, $statuses] = $this->reports();

        return $this->renderTemplate(
            '@SDG/SDG.twig',
            [
                'topMenu' => MenuTop::getInstance()->getMenu(),
                'adminMenu' => MenuAdmin::getInstance()->getMenu(),
                'notifications' => $notifications,
                'rows' => $reports,
                'siteIds' => $siteIds,
                'names' => $names,
                'fromDates' => $fromDates,
                'toDates' => $toDates,
                'statuses' => $statuses,
            ]
        );
    }

    /**
     */
    public function filter(): string
    {
        Piwik::checkUserHasSomeAdminAccess();

        $siteId = \Piwik\Request::fromRequest()->getStringParameter('siteId');
        $siteId = strip_tags($siteId);
        if (isset($siteId)) {
            $siteId = 0;
        }
        $name = \Piwik\Request::fromRequest()->getStringParameter('name');
        $name = strip_tags($name);
        $from = \Piwik\Request::fromRequest()->getStringParameter('from');
        $from = strip_tags($from);
        $to = \Piwik\Request::fromRequest()->getStringParameter('to');
        $to = strip_tags($to);
        $status = \Piwik\Request::fromRequest()->getStringParameter('status');
        $status = strip_tags($status);

        [$reports, $siteIds, $names, $fromDates, $toDates, $statuses] = $this->reports();

        foreach ($reports as $id => $report) {
            if (
                ($siteId != 0 && $reports[$id]['idSite'] != $siteId)
                || (!empty($name) && $reports[$id]['reportType'] != $name)
                || (!empty($from) && strtotime($reports[$id]['fromdate']) < strtotime($from))
                || (!empty($to) && strtotime($reports[$id]['todate']) > strtotime($to))
                || (!empty($status) && $reports[$id]['status'] != $status)
            ) {
                unset($reports[$id]);
            }
        }

        return $this->renderTemplate(
            '@SDG/_sdgTable.twig',
            [
                'rows' => $reports,
                'statuses' => $statuses,
                'siteIds' => $siteIds,
                'names' => $names,
                'fromDates' => $fromDates,
                'toDates' => $toDates,
            ]
        );
    }
    public function professional()
    {
        return $this->renderTemplate(
            '@SDG/professional.twig',
            [
                'topMenu' => MenuTop::getInstance()->getMenu(),
                'adminMenu' => MenuAdmin::getInstance()->getMenu(),
                'notifications' => $notifications,
            ]
        );
    }
    public function docs()
    {
        return $this->renderTemplate(
            '@SDG/docs.twig',
            [
                'topMenu' => MenuTop::getInstance()->getMenu(),
                'adminMenu' => MenuAdmin::getInstance()->getMenu(),
                'notifications' => $notifications,
            ]
        );
    }
}
