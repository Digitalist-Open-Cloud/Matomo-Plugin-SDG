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
use Piwik\Nonce;
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
    private const RESEND_NONCE = 'SDG.resend';

    /**
     *
     * @var Reports
     */
    protected $db;

    public function __construct(Reports $db)
    {
        $this->db = $db;
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

        $nonce = Nonce::getNonce(self::RESEND_NONCE);

        foreach ($this->db->getSentReports() as $report) {
            if (!Piwik::isUserHasAdminAccess($report['idSite'])) {
                continue;
            }

            $resend_url = Url::getCurrentQueryStringWithParametersModified(
                [
                    'module' => 'SDG',
                    'action' => 'resend',
                    'uniqueid' => $report['uniqueid'],
                    'nonce' => $nonce,
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
        Nonce::checkNonce(self::RESEND_NONCE, \Piwik\Request::fromRequest()->getStringParameter('nonce', ''));

        // What is sent again is read from the transfer that was recorded, not from the request.
        $report = $this->db->getSentReport(\Piwik\Request::fromRequest()->getStringParameter('uniqueid', ''));
        if ($report === null) {
            throw new \Exception(Piwik::translate('SDG_ErrorSendingLogs'));
        }
        Piwik::checkUserHasAdminAccess($report['idSite']);

        try {
            $sois = new SOIS(
                ['idsite' => (int) $report['idSite']],
                date('Y-m-d', strtotime($report['fromdate'])),
                date('Y-m-d', strtotime($report['todate'])),
                true,
                $report['uniqueid']
            );
            $isSent = $sois->send()['status'] === 200;
        } catch (\Exception $e) {
            $isSent = false;
        }

        $notification = new Notification(Piwik::translate($isSent ? 'SDG_LogsSent' : 'SDG_ErrorSendingLogs'));
        $notification->context = $isSent ? Notification::CONTEXT_SUCCESS : Notification::CONTEXT_ERROR;

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

        $request = \Piwik\Request::fromRequest();
        $siteId = (int) $request->getStringParameter('siteId', '');
        $name = strip_tags($request->getStringParameter('name', ''));
        $from = strip_tags($request->getStringParameter('from', ''));
        $to = strip_tags($request->getStringParameter('to', ''));
        $status = strip_tags($request->getStringParameter('status', ''));

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
        Piwik::checkUserHasSomeAdminAccess();

        return $this->renderTemplate(
            '@SDG/professional.twig',
            [
                'topMenu' => MenuTop::getInstance()->getMenu(),
                'adminMenu' => MenuAdmin::getInstance()->getMenu(),
            ]
        );
    }

    public function docs()
    {
        Piwik::checkUserHasSomeAdminAccess();

        return $this->renderTemplate(
            '@SDG/docs.twig',
            [
                'topMenu' => MenuTop::getInstance()->getMenu(),
                'adminMenu' => MenuAdmin::getInstance()->getMenu(),
            ]
        );
    }
}
