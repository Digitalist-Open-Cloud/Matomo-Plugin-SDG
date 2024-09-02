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

use Piwik\Piwik;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for SDG.
 *
 */
class MeasurableSettings extends \Piwik\Settings\Measurable\MeasurableSettings
{
    public $reportUrls;

    public $apiKey;

    public $acceptanceApiKey;


    protected function init()
    {
        $this->apiKey = $this->createApiKeySetting();
        $this->acceptanceApiKey = $this->createAcceptanceApiKeySetting();
        $this->reportUrls = $this->createReportUrlsSetting();
    }

    public function createApiKeySetting()
    {
        $defaultValue = '';
        $type = FieldConfig::TYPE_STRING;

        return $this->makeSetting('sdg_api_key', $defaultValue, $type, function (FieldConfig $field) {
            $field->title = Piwik::translate('SDG_ApiKey');
            $field->inlineHelp = Piwik::translate('SDG_ApiKeyInlineHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
        });
    }


    public function createAcceptanceApiKeySetting()
    {
        $defaultValue = '';
        $type = FieldConfig::TYPE_STRING;

        return $this->makeSetting('sdg_acceptance_api_key', $defaultValue, $type, function (FieldConfig $field) {
            $field->title = Piwik::translate('SDG_AcceptanceApiKey');
            $field->inlineHelp = Piwik::translate('SDG_AcceptanceApiKeyInlineHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
        });
    }

    private function createReportUrlsSetting()
    {
        $defaultValue = '';
        $type = FieldConfig::TYPE_STRING;
        return $this->makeSetting(
            'sdg_report_urls',
            $defaultValue,
            $type,
            function (FieldConfig $field) {
                $field->title = Piwik::translate('SDG_ReportURLs');
                $field->inlineHelp = Piwik::translate('SDG_ReportURLsInlineHelp');
                $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            }
        );
    }
}
