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

use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSettings as PiwikSystemSettings;
use Piwik\Validators\NotEmpty;

/**
 * Defines System Settings (general settings) for SDG.
 */
class SystemSettings extends PiwikSystemSettings
{
    public $environment;
    public $senddate;

    protected function init()
    {
        $this->environment = $this->sdgEnvironmnet();
        $this->senddate = $this->sdgSendDate();
    }

    /**
     * Set which environmnet to use, Prod (live) or Testing (acceptance).
     * Defaults to prod.
     */
    private function sdgEnvironmnet()
    {
        $default = ['live'];
        return $this->makeSetting('sdgEnvironmnet', $default, FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = 'Set SDG environment';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->availableValues = ['acceptance' => 'Testing (acceptance)', 'live' => 'Production'];
            $field->description = 'Settings for sending SDG statistics against production or acceptance environment.';
        });
    }
    /**
     * Set which environmnet to use, Prod (live) or Testing (acceptance).
     * Defaults to prod.
     */
    private function sdgSendDate()
    {
        return $this->makeSetting(
            'sdgSendDate',
            $default = '2',
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title = 'Date to send SDG from previous month';
                $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
                $dates = array_combine(range(1, 31), range(1, 31));
                $field->availableValues = $dates;
                $field->description = 'Choose the date of month to send statistics to SDG '
                . 'for previous month. this will be used for the scheduled task to automate '
                . 'the sending of the statistics. Recommended to set to the second (2), to '
                . 'be sure that you have all information needed archived.';
                $field->validators[] = new NotEmpty();
            }
        );
    }
}
