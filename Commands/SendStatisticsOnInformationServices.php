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

declare(strict_types=1);

namespace Piwik\Plugins\SDG\Commands;

use DateTime;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\SDG\Service\SOIS;

/**
 * SendStatisticsOnInformationServices
 */
class SendStatisticsOnInformationServices extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('sdg:send-statistics-on-information-services');
        $this->setDescription('Send statistics for information services.');
        $this->addNoValueOption(
            'send',
            null,
            'send the data'
        );
        $this->addRequiredValueOption(
            'idsite',
            null,
            'Site id'
        );
        $this->addRequiredValueOption(
            'from',
            null,
            'From date, (format 2023-01-01)'
        );
        $this->addRequiredValueOption(
            'to',
            null,
            'To date, (format 2023-01-31)'
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $config = [];
        $send = false;
        $id = (int)$input->getOption('idsite');
        $config['idsite'] = $id;
        $send = $input->getOption('send');
        $from = (string)$input->getOption('from');
        if (DateTime::createFromFormat('Y-m-d', $from) == false) {
            $output->writeln("<error>$from doesn't like a valid date string, like 2023-01-01");
            return 1;
        }
        $to = (string)$input->getOption('to');
        if (DateTime::createFromFormat('Y-m-d', $to) == false) {
            $output->writeln("<error>$to doesn't like a valid date string, like 2023-01-31");
            return 1;
        }

        $sois = new SOIS($config, $from, $to, $send);
        $result = $sois->send();

        if ($result['status'] == 'dump') {
            $output->writeln("<info>Dumping, as send parameter wasn't provided</info>");
            $output->writeln($result['payload']);
            return self::SUCCESS;
        }
        if ($result['status'] == '200') {
            $output->writeln("<info>All data sent successful.</info>");
            return self::SUCCESS;
        } else {
            $status = $result['status'];
            $output->writeln("<warning>Status $status, data wasn't sent correctly. </warning>");
            return self::FAILURE;
        }
    }
}
