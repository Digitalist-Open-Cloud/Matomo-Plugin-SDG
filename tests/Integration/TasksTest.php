<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Log\LoggerInterface;
use Piwik\Piwik;
use Piwik\Plugins\SDG\SystemSettings;
use Piwik\Plugins\SDG\Tasks;
use Piwik\Scheduler\RetryableException;

/**
 * The send date is the second of the month, unless a test sets another.
 *
 * @group SDG
 * @group Plugins
 */
class TasksTest extends SdgTestCase
{
    /**
     * @var int
     */
    private $uniqueIds = 0;

    public function setUp(): void
    {
        parent::setUp();

        $this->configure(1, ['apiKey' => 'the-key-of-site-1', 'reportUrls' => 'example.org/services']);
        $this->trackVisit(1, ['https://example.org/services']);
    }

    public function tearDown(): void
    {
        Date::$now = null;

        parent::tearDown();
    }

    public function testThePreviousMonthIsSentOnTheSendDateForWebsitesWithReportUrls()
    {
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-02');

        $this->assertSame([
            'https://collect.youreurope.europa.eu/v1/unique-id',
            'https://collect.youreurope.europa.eu/v2/statistics/information-services',
        ], array_column($this->requests, 'url'));
        $this->assertContains('x-api-key: the-key-of-site-1', $this->requests[1]['headers']);
        $this->assertSame(
            ['startDate' => '2024-03-01T00:00:00.00Z', 'endDate' => '2024-03-31T00:00:00.00Z'],
            json_decode($this->requests[1]['body'], true)['referencePeriod']
        );
        $this->assertEquals([['uniqueid' => 'the-id', 'idSite' => 1, 'status' => 200, 'nbEntries' => 1]], $this->getTransfers());
    }

    public function testNothingIsSentBeforeTheSendDate()
    {
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-01');

        $this->assertSame([], $this->requests);
    }

    public function testAMonthThatWasNotSentOnTheSendDateIsSentLater()
    {
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-05');

        $this->assertEquals(200, $this->getTransfers()[0]['status']);
    }

    public function testASendDateTheMonthDoesNotHaveIsItsLastDay()
    {
        $this->sendOn(31);
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-29');
        $this->assertSame([], $this->requests);

        $this->runTaskOn('2024-04-30');
        $this->assertEquals(200, $this->getTransfers()[0]['status']);
    }

    public function testAMonthThePortalAcceptedIsNotSentAgain()
    {
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-02');
        $this->runTaskOn('2024-04-02');
        $this->runTaskOn('2024-04-03');

        $this->assertCount(2, $this->requests);
        $this->assertCount(1, $this->getTransfers());
    }

    public function testAMonthThePortalRefusedIsSentAgainTheNextDay()
    {
        // The portal gives out unique IDs and refuses the statistics.
        Piwik::addAction('Http.sendHttpRequest', function ($url, $params, &$response, &$status) {
            $isUniqueId = substr($url, -strlen('/unique-id')) === '/unique-id';
            $response = $isUniqueId ? '"id-' . ++$this->uniqueIds . '"' : 'Bad request';
            $status = $isUniqueId ? 200 : 400;
        });

        $this->runTaskOn('2024-04-02');
        $this->runTaskOn('2024-04-02');
        $this->assertCount(1, $this->getTransfers());

        $this->runTaskOn('2024-04-03');
        $this->assertCount(2, $this->getTransfers());
    }

    public function testAWebsiteIsSentWhateverItsReportUrlsStartWith()
    {
        $this->configure(1, ['reportUrls' => ',example.org/services']);
        $this->answer(200, '"the-id"');

        $this->runTaskOn('2024-04-02');

        $this->assertCount(1, $this->getTransfers());
    }

    public function testTheTaskIsTriedAgainWhenThePortalCannotBeUsed()
    {
        $this->answer(500, 'Internal server error');

        $this->expectException(RetryableException::class);
        $this->runTaskOn('2024-04-02');
    }

    private function sendOn(int $day): void
    {
        $settings = new SystemSettings();
        $settings->senddate->setValue((string) $day);
        $settings->save();
    }

    private function runTaskOn(string $day): void
    {
        Date::$now = strtotime($day . ' 08:00:00 UTC');

        (new Tasks(StaticContainer::get(LoggerInterface::class)))->sendInformationServicesData();
    }
}
