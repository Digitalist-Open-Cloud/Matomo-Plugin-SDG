<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\SDG\MeasurableSettings;
use Piwik\Plugins\SDG\SystemSettings;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group SDG
 * @group Plugins
 */
class CommandsTest extends ConsoleCommandTestCase
{
    /**
     * @var string[] the URLs of the requests that were sent to the portal
     */
    private $requests = [];

    public function setUp(): void
    {
        parent::setUp();

        // The database is kept between the tests of this class.
        while (count(SitesManagerAPI::getInstance()->getAllSitesId()) < 2) {
            Fixture::createWebsite('2024-01-01 00:00:00');
        }
        foreach ([1, 2] as $idSite) {
            $settings = new MeasurableSettings($idSite);
            $settings->apiKey->setValue('');
            $settings->reportUrls->setValue('');
            $settings->save();
        }
        $settings = new SystemSettings();
        $settings->environment->setValue(['live']);
        $settings->save();
        Db::exec('DELETE FROM ' . Common::prefixTable('sdg'));
        $this->requests = [];
        putenv('SDG_API_KEY');
    }

    public function tearDown(): void
    {
        putenv('SDG_API_KEY');

        parent::tearDown();
    }

    public function testTheApiKeyOfAWebsiteIsSet()
    {
        $this->assertSame(0, $this->runCommand('sdg:set-api-key', ['--idsite' => 1, '--key' => 'the-key']));

        $this->assertSame('the-key', (new MeasurableSettings(1))->apiKey->getValue());
    }

    public function testTheApiKeyIsReadFromTheEnvironmentWithoutTheOption()
    {
        putenv('SDG_API_KEY=the-key-of-the-environment');

        $this->assertSame(0, $this->runCommand('sdg:set-api-key', ['--idsite' => 1]));

        $this->assertSame('the-key-of-the-environment', (new MeasurableSettings(1))->apiKey->getValue());
    }

    public function testSettingTheApiKeyFailsWithoutAKeyOrAWebsite()
    {
        $this->assertSame(1, $this->runCommand('sdg:set-api-key', ['--idsite' => 1]));
        $this->assertSame(1, $this->runCommand('sdg:set-api-key', ['--key' => 'the-key']));

        $this->assertSame('', (new MeasurableSettings(1))->apiKey->getValue());
    }

    public function testReportUrlsAreAddedToTheOnesOfTheWebsiteWithoutDuplicates()
    {
        $this->runCommand('sdg:set-urls', ['--idsite' => 1, '--urls' => 'example.org/services,example.org/permits']);
        $this->assertSame(0, $this->runCommand('sdg:set-urls', ['--idsite' => 1, '--urls' => 'example.org/permits,example.org/taxes']));

        $this->assertSame('example.org/services,example.org/permits,example.org/taxes', (new MeasurableSettings(1))->reportUrls->getValue());
    }

    public function testReportUrlsAreReplacedWhenAskedTo()
    {
        $this->runCommand('sdg:set-urls', ['--idsite' => 1, '--urls' => 'example.org/services']);
        $this->runCommand('sdg:set-urls', ['--idsite' => 1, '--urls' => 'example.org/taxes', '--no-append' => true]);

        $this->assertSame('example.org/taxes', (new MeasurableSettings(1))->reportUrls->getValue());
    }

    public function testSettingReportUrlsFailsWithoutUrlsOrAWebsite()
    {
        $this->assertSame(1, $this->runCommand('sdg:set-urls', ['--idsite' => 1]));
        $this->assertSame(1, $this->runCommand('sdg:set-urls', ['--urls' => 'example.org/services']));
    }

    public function testTheReportUrlsOfAllWebsitesAreListed()
    {
        $this->runCommand('sdg:set-urls', ['--idsite' => 1, '--urls' => 'example.org/services']);
        $this->runCommand('sdg:set-urls', ['--idsite' => 2, '--urls' => 'example.com/permits']);

        $this->assertSame(0, $this->runCommand('sdg:get-urls', []));

        $this->assertStringContainsString('example.org/services', $this->applicationTester->getDisplay());
        $this->assertStringContainsString('example.com/permits', $this->applicationTester->getDisplay());
    }

    public function testTheEnvironmentIsShown()
    {
        $this->runCommand('sdg:environment', []);
        $this->assertStringContainsString('live', $this->applicationTester->getDisplay());

        $settings = new SystemSettings();
        $settings->environment->setValue(['acceptance']);
        $settings->save();

        $this->runCommand('sdg:environment', []);
        $this->assertStringContainsString('acceptance', $this->applicationTester->getDisplay());
    }

    public function testStatisticsAreOnlyShownWithoutTheSendOption()
    {
        $this->answer(200, '"the-id"');

        $exitCode = $this->runCommand('sdg:send-statistics-on-information-services', ['--idsite' => 1, '--from' => '2024-03-01', '--to' => '2024-03-31']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"uniqueId":"the-id"', $this->applicationTester->getDisplay());
        $this->assertStringContainsString('"startDate":"2024-03-01T00:00:00.00Z"', $this->applicationTester->getDisplay());
        $this->assertSame(['https://collect.youreurope.europa.eu/v1/unique-id'], $this->requests);
    }

    public function testStatisticsAreSentWithTheSendOption()
    {
        $this->answer(200, '"the-id"');

        $exitCode = $this->runCommand('sdg:send-statistics-on-information-services', ['--idsite' => 1, '--from' => '2024-03-01', '--to' => '2024-03-31', '--send' => true]);

        $this->assertSame(0, $exitCode, $this->applicationTester->getDisplay());
        $this->assertSame('https://collect.youreurope.europa.eu/v2/statistics/information-services', $this->requests[1]);
        $this->assertEquals(200, Db::fetchOne('SELECT status FROM ' . Common::prefixTable('sdg') . " WHERE uniqueid = 'the-id'"));
    }

    public function testSendingFailsWhenThePortalRefuses()
    {
        Piwik::addAction('Http.sendHttpRequest', function ($url, $params, &$response, &$status) {
            $isUniqueId = substr($url, -strlen('/unique-id')) === '/unique-id';
            $response = $isUniqueId ? '"the-id"' : 'Bad request';
            $status = $isUniqueId ? 200 : 400;
        });

        $exitCode = $this->runCommand('sdg:send-statistics-on-information-services', ['--idsite' => 1, '--from' => '2024-03-01', '--to' => '2024-03-31', '--send' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('400', $this->applicationTester->getDisplay());
    }

    /**
     * @dataProvider getDatesThatAreNotDates
     */
    public function testSendingFailsForDatesThePortalDoesNotTake(string $from, string $to)
    {
        $this->answer(200, '"the-id"');

        $exitCode = $this->runCommand('sdg:send-statistics-on-information-services', ['--idsite' => 1, '--from' => $from, '--to' => $to, '--send' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertSame([], $this->requests);
    }

    public function getDatesThatAreNotDates(): array
    {
        return [
            'from' => ['March', '2024-03-31'],
            'to' => ['2024-03-01', '31/3'],
            'not from the first day of a month' => ['2024-03-05', '2024-03-31'],
        ];
    }

    /**
     * Makes the portal answer every request with this.
     */
    private function answer(int $status, string $body): void
    {
        Piwik::addAction('Http.sendHttpRequest', function ($url, $params, &$response, &$responseStatus) use ($status, $body) {
            $this->requests[] = $url;
            $response = $body;
            $responseStatus = $status;
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runCommand(string $command, array $options): int
    {
        return $this->applicationTester->run(['command' => $command] + $options, ['interactive' => false]);
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

        $fixture->createSuperUser = true;
        $fixture->extraPluginsToLoad = ['SDG'];
    }
}
