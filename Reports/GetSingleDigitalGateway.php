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

namespace Piwik\Plugins\SDG\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;

/**
 *
 */
class GetSingleDigitalGateway extends Base
{
    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('SDG_SDGSingleDigitalGatewayStatistics');
        $this->dimension = null;
        $this->documentation = Piwik::translate('SDG_SDGHelp');
        $this->order = 99;
        $this->processedMetrics = array();
        $this->subcategoryId = 'SDG_SingleDigitalGatewayStatistics';
    }

    /**
     * @param ViewDataTable $view
     */
    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = true;
        $view->requestConfig->filter_sort_column = 'nb_visits';
  //      $view->config->columns_to_display = array_merge(['url'], $this->metrics);
        $view->config->show_tag_cloud = false;
        $view->config->show_pie_chart = false;
        $view->config->show_table_all_columns = true;
        $view->config->columns_to_display  = array('url','pageTitle','country','deviceType','nb_visits');
    }
}
