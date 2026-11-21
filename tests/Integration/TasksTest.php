<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\SDG\SystemSettings;
use Piwik\Plugins\SDG\Tasks;
use Piwik\Scheduler\RetryableException;

/**
 * @group SDG
 * @group Plugins
 */
class TasksTest extends SdgTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configure(1, ['apiKey' => 'the-key-of-site-1', 'reportUrls' => 'example.org/services']);
    }

    public function testThePreviousMonthIsSentOnTheSendDateForWebsitesWithReportUrls()
    {
        $this->sendOn((int) date('j'));
        $this->answer(200, '"the-id"');

        $this->runTask();

        $this->assertSame([
            'https://collect.youreurope.europa.eu/v1/unique-id',
            'https://collect.youreurope.europa.eu/v2/statistics/information-services',
        ], array_column($this->requests, 'url'));
        $this->assertContains('x-api-key: the-key-of-site-1', $this->requests[1]['headers']);
        $this->assertSame(
            ['startDate' => date('Y-m-d', strtotime('first day of -1 month')) . 'T00:00:00.00Z', 'endDate' => date('Y-m-d', strtotime('last day of -1 month')) . 'T00:00:00.00Z'],
            json_decode($this->requests[1]['body'], true)['referencePeriod']
        );
        $this->assertEquals([['uniqueid' => 'the-id', 'idSite' => 1, 'status' => 200, 'nbEntries' => 0]], $this->getTransfers());
    }

    public function testAWebsiteIsSentWhateverItsReportUrlsStartWith()
    {
        $this->configure(1, ['reportUrls' => ',example.org/services']);
        $this->sendOn((int) date('j'));
        $this->answer(200, '"the-id"');

        $this->runTask();

        $this->assertCount(1, $this->getTransfers());
    }

    public function testAMonthThatWasSentIsNotSentAgain()
    {
        $this->sendOn((int) date('j'));
        $this->answer(200, '"the-id"');

        $this->runTask();
        $this->runTask();

        $this->assertCount(2, $this->requests);
        $this->assertCount(1, $this->getTransfers());
    }

    public function testNothingIsSentOnAnotherDayThanTheSendDate()
    {
        $this->sendOn(date('j') === '1' ? 2 : (int) date('j') - 1);
        $this->answer(200, '"the-id"');

        $this->runTask();

        $this->assertSame([], $this->requests);
    }

    public function testTheTaskIsTriedAgainWhenThePortalCannotBeUsed()
    {
        $this->sendOn((int) date('j'));
        $this->answer(500, 'Internal server error');

        $this->expectException(RetryableException::class);
        $this->runTask();
    }

    private function sendOn(int $day): void
    {
        $settings = new SystemSettings();
        $settings->senddate->setValue((string) $day);
        $settings->save();
    }

    private function runTask(): void
    {
        (new Tasks(StaticContainer::get(LoggerInterface::class)))->sendInformationServicesData();
    }
}
