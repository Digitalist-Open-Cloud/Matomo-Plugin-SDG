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
 * SetSdgUrls
 */
class SetSdgUrls extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> sets Urls that should be sent to SDG.
<comment>Samples:</comment>
To run:
<info>%command.name% --idsite=1 --urls=example.org,example.org/blog</info>';
        $this->setHelp($HelpText);
        $this->setName('sdg:set-urls');
        $this->setDescription('Add URLs to send to SDG');

        $this->addRequiredValueOption(
            'idsite',
            null,
            'Site id'
        );
        $this->addRequiredValueOption(
            'urls',
            null,
            'Your URLs, comma seperated',
        );
        $this->addNoValueOption(
            'no-append',
            null,
            "Don't Append urls to existing, replace them."
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $idsite = $input->getOption('idsite');
        $urls = $input->getOption('urls');
        $no_append = $input->getOption('no-append');
        if (!(isset($idsite))) {
            $output->writeln('<error>You need to provide a site id</error>');
            return self::FAILURE;
        }
        if (!(isset($urls))) {
            $output->writeln('<error>You need to provide at least one url</error>');
            return self::FAILURE;
        }
        if ($this->getUrls($idsite, $urls)) {
            $output->writeln('<info>Existing URL:s ' . $this->getUrls($idsite, $urls) .  ' </info>');
        }
        $this->setUrls($idsite, $urls, $no_append);

        $output->writeln('<info>New URL:s added: ' . $urls . '</info>');
        return self::SUCCESS;
    }

    /**
     * @param string $idsite
     * @param string $urls
     * @param bool $no_append
     * @return true
     */
    private function setUrls($idsite, $urls, $no_append)
    {
        $settings = new MeasurableSettings((int) $idsite);
        $existing = $settings->reportUrls->getValue();
        if ($no_append === false) {
            $existing = $settings->reportUrls->getValue();
            $urls = "$existing,$urls";
        }
        $cleanUrls = $this->removeDuplicates($urls);
        $settings->reportUrls->setValue($cleanUrls);
        $settings->save();
        return self::SUCCESS;
    }

    private function getUrls($idsite, $urls)
    {
        $settings = new MeasurableSettings((int) $idsite);
        return $settings->reportUrls->getValue();
    }

    private function removeDuplicates($urls)
    {
        return implode(',', array_unique(explode(',', $urls)));
    }
}
