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
use Piwik\Common;
use Piwik\Db;

class SDG extends \Piwik\Plugin
{
    public const PLUGIN_NAME = "SDG";
    public static $tableName = 'sdg';

    /**
     * Events to register.
     */
    public function registerEvents()
    {
        return [
            'CronArchive.getArchivingAPIMethodForPlugin' => 'getArchivingAPIMethodForPlugin',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        ];
    }

    /**
     * Get translations strings to js.
     */
    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'SDG_SingleDigitalGateway';
        $translationKeys[] = 'SDG_ApiKey';
        $translationKeys[] = 'SDG_ApiKeyInlineHelp';
        $translationKeys[] = 'SDG_ReportURLs';
        $translationKeys[] = 'SDG_ReportURLsInlineHelp';
        $translationKeys[] = 'SDG_LogsSent';
        $translationKeys[] = 'SDG_ErrorSendingLogs';
        $translationKeys[] = 'SDG_FilterBy';
        $translationKeys[] = 'SDG_ReportText';
        $translationKeys[] = 'SDG_SiteID';
        $translationKeys[] = 'SDG_ReportType';
        $translationKeys[] = 'SDG_From';
        $translationKeys[] = 'SDG_To';
        $translationKeys[] = 'SDG_Period';
        $translationKeys[] = 'SDG_TimeSent';
        $translationKeys[] = 'SDG_TransferDate';
        $translationKeys[] = 'SDG_Response';
        $translationKeys[] = 'SDG_Resend';
        $translationKeys[] = 'SDG_SDGSingleDigitalGatewayStatistics';
        $translationKeys[] = 'SDG_SDG';
        $translationKeys[] = 'SDG_SDGHelp';
    }

    /**
     * Load defined JS files into Matomo.
     */
    public function getJavaScriptFiles(&$files)
    {
        $files[] = "plugins/SDG/js/sdg.js";
    }
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/SDG/stylesheet/sdg.less";
    }


    /**
     * Runs when the plugin is installed.
     * Creating needed tables.
     */
    public function install()
    {
        try {
            $sql = "CREATE TABLE " . Common::prefixTable(self::$tableName) . " (
                        id INT NOT NULL AUTO_INCREMENT
                        , uniqueid VARCHAR(128) NOT NULL
                        , reportType VARCHAR(128) NOT NULL
                        , idSite INTEGER NOT NULL
                        , fromdate datetime NOT NULL
                        , todate datetime NOT NULL
                        , status VARCHAR(100) NOT NULL
                        , nbEntries INTEGER
                        , lastChange TIMESTAMP
                        , PRIMARY KEY (id)
                        , UNIQUE KEY (uniqueid)
                    ) DEFAULT CHARSET=utf8mb4";
            // phpcs:disable
            Db::exec($sql);
            // phpcs:enable
        } catch (Exception $e) {
            // ignore error if table already exists (1050 code is for 'table already exists')
            if (!Db::get()->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }

    /**
     * Runs when the plugin is uninstalled, removing the tables.
     */
    public function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::$tableName));
    }
}
