<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\NoAccessException;
use Piwik\Nonce;
use Piwik\Plugins\SDG\API;
use Piwik\Plugins\SDG\Controller;
use Piwik\Plugins\SDG\Model\Reports;
use Piwik\Plugins\SDG\Service\Sql;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group SDG
 * @group Plugins
 */
class AccessTest extends SdgTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    public function setUp(): void
    {
        parent::setUp();

        FakeAccess::clearAccess(true);
        $this->configure(1, ['apiKey' => 'the-key-of-site-1', 'reportUrls' => 'example.org/services']);
        $this->configure(2, ['apiKey' => 'the-key-of-site-2', 'reportUrls' => 'example.org/other']);
        (new Sql())->insertRequestInfo('uuid-of-site-1', 'information-services', 1, '2024-03-01', '2024-03-31', 403, 0, '2024-04-02');
        (new Sql())->insertRequestInfo('uuid-of-site-2', 'information-services', 2, '2024-03-01', '2024-03-31', 403, 0, '2024-04-02');

        $this->answer(200);
        $this->controller = new Controller(new Reports(), StaticContainer::get(LoggerInterface::class));
        $_GET = [];
    }

    public function tearDown(): void
    {
        $_GET = [];

        parent::tearDown();
    }

    public function testTheReportNeedsViewAccessToTheWebsite()
    {
        FakeAccess::clearAccess(false, [], [1]);

        $this->assertSame(0, API::getInstance()->getSingleDigitalGateway(1, 'day', self::DAY)->getRowsCount());

        $this->expectException(NoAccessException::class);
        API::getInstance()->getSingleDigitalGateway(2, 'day', self::DAY);
    }

    public function testTheStatusPageOnlyListsTheTransfersOfWebsitesTheUserIsAdminOf()
    {
        FakeAccess::clearAccess(false, [1]);

        $reports = new \ReflectionMethod(Controller::class, 'reports');
        $reports->setAccessible(true);

        $this->assertSame(['uuid-of-site-1'], array_column($reports->invoke($this->controller)[0], 'uniqueid'));
    }

    public function testATransferIsNotSentAgainWithoutTheTokenOfTheStatusPage()
    {
        FakeAccess::clearAccess(false, [1]);
        $_GET = ['uniqueid' => 'uuid-of-site-1', 'idSite' => '1', 'from' => '2024-03-01', 'to' => '2024-03-31'];

        try {
            $this->controller->resend();
            $this->fail('The transfer was sent again.');
        } catch (\Exception $e) {
            $this->assertSame([], $this->requests);
        }
    }

    public function testATransferOfAnotherWebsiteIsNotSentAgain()
    {
        FakeAccess::clearAccess(false, [1]);
        $_GET = ['uniqueid' => 'uuid-of-site-2', 'nonce' => Nonce::getNonce('SDG.resend')];

        try {
            $this->controller->resend();
            $this->fail('The transfer was sent again.');
        } catch (NoAccessException $e) {
            $this->assertSame([], $this->requests);
        }
    }

    public function testWhatIsSentAgainIsTheTransferThatWasRecordedAndNotWhatTheRequestSays()
    {
        FakeAccess::clearAccess(false, [1]);
        $_GET = ['uniqueid' => 'uuid-of-site-1', 'nonce' => Nonce::getNonce('SDG.resend'), 'from' => '2020-01-01', 'to' => '2020-12-31'];

        $this->controller->resend();

        $this->assertCount(1, $this->requests);
        $this->assertContains('x-api-key: the-key-of-site-1', $this->requests[0]['headers']);
        $this->assertStringContainsString('2024-03-01', $this->requests[0]['body']);
        $this->assertEquals(200, $this->getTransfers()[0]['status']);
    }

    public function testATransferNobodyRecordedIsNotSent()
    {
        FakeAccess::clearAccess(false, [1]);
        $_GET = [
            'uniqueid' => "x', 'a', 1, now(), now(), 200, 1, now()) -- ",
            'nonce' => Nonce::getNonce('SDG.resend'),
            'idSite' => '1',
            'from' => '2024-03-01',
            'to' => '2024-03-31',
        ];

        try {
            $this->controller->resend();
            $this->fail('The transfer was sent.');
        } catch (\Exception $e) {
            $this->assertSame([], $this->requests);
            $this->assertCount(2, $this->getTransfers());
        }
    }

    /**
     * @dataProvider getFilters
     */
    public function testTheStatusPageIsFiltered(array $filter, array $expected)
    {
        (new Sql())->insertRequestInfo('uuid-of-february', 'information-services', 1, '2024-02-01', '2024-02-29', 200, 4, '2024-03-02');
        $_GET = $filter;

        $table = $this->controller->filter();

        foreach (['uuid-of-site-1', 'uuid-of-site-2', 'uuid-of-february'] as $uniqueId) {
            $this->assertSame(in_array($uniqueId, $expected), strpos($table, $uniqueId) !== false, $uniqueId);
        }
    }

    public function getFilters(): array
    {
        return [
            'nothing' => [[], ['uuid-of-site-1', 'uuid-of-site-2', 'uuid-of-february']],
            'website' => [['siteId' => '2'], ['uuid-of-site-2']],
            'status' => [['status' => '200'], ['uuid-of-february']],
            'from' => [['from' => '2024-03-01'], ['uuid-of-site-1', 'uuid-of-site-2']],
            'to' => [['to' => '2024-02-29'], ['uuid-of-february']],
            'type' => [['name' => 'another-type'], []],
        ];
    }

    public function testTheDocumentationNeedsAdminAccess()
    {
        FakeAccess::clearAccess(false, [], [1]);

        $this->expectException(NoAccessException::class);
        $this->controller->docs();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\Access' => new FakeAccess()];
    }
}
