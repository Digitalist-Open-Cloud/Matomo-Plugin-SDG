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

namespace Piwik\Plugins\SDG\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\SDG\MeasurableSettings;

/**
 * SetSdgApiKey
 */
class SetSdgApiKey extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> sets SDG API key.
<comment>Samples:</comment>
To run:
<info>%command.name% --key=FOOBAR</info>';
        $this->setHelp($HelpText);
        $this->setName('sdg:set-api-key');
        $this->setDescription('Set SDG API key');
        $this->addRequiredValueOption(
            'idsite',
            null,
            'Site id'
        );
        $this->addRequiredValueOption(
            'key',
            null,
            'Your API key'
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $idsite = $input->getOption('idsite');
        $key = $input->getOption('key');
        if (!(isset($idsite))) {
            $output->writeln('<error>you need to provide a site id</error>');
            return self::FAILURE;
        }
        if (!(isset($key))) {
            $output->writeln('<error>you need to provide an api key</error>');
            return self::FAILURE;
        }
        $this->setKey($idsite, $key);
        $output->writeln('<info>Key added</info>');
        return self::SUCCESS;
    }

    private function setKey($idsite, $key)
    {
        $settings = new MeasurableSettings((int) $idsite);
        $settings->apiKey->setValue($key);
        $settings->save();
        return self::SUCCESS;
    }
}
