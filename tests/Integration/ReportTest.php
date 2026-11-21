<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\API\Request;
use Piwik\Plugins\SDG\Lib\GetAPIConfig;
use Piwik\Plugins\SDG\Service\SOIS;
use Piwik\Plugins\SDG\SystemSettings;
use Piwik\Tests\Framework\Fixture;

/**
 * @group SDG
 * @group Plugins
 */
class ReportTest extends SdgTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        $this->configure(1, ['apiKey' => 'the-live-key', 'acceptanceApiKey' => 'the-acceptance-key', 'reportUrls' => 'example.org/services,example.org/permits']);
        $this->trackVisit(1, ['https://example.org/services', 'https://example.org/permits', 'https://example.org/about']);
        $this->trackVisit(1, ['https://example.org/services']);
        $this->trackVisit(1, ['https://example.org/services'], '2024-03-06');
    }

    public function testTheReportCountsVisitsPerPageWithCountryAndDevice()
    {
        $report = Request::processRequest('SDG.getSingleDigitalGateway', ['idSite' => 1, 'period' => 'day', 'date' => self::DAY]);

        $visitsByUrl = array_combine($report->getColumn('url'), $report->getColumn('nb_visits'));
        ksort($visitsByUrl);
        $this->assertEquals(['example.org/permits' => 1, 'example.org/services' => 2], $visitsByUrl);

        $row = $report->getFirstRow()->getColumns();
        $this->assertNotEmpty($row['pageTitle']);
        $this->assertNotEmpty($row['country']);
        $this->assertSame('Desktop', $row['deviceType']);
    }

    public function testAWeekAddsUpItsDays()
    {
        $report = Request::processRequest('SDG.getSingleDigitalGateway', ['idSite' => 1, 'period' => 'week', 'date' => self::DAY]);

        $visitsByUrl = array_combine($report->getColumn('url'), $report->getColumn('nb_visits'));
        ksort($visitsByUrl);
        $this->assertEquals(['example.org/permits' => 1, 'example.org/services' => 3], $visitsByUrl);
    }

    public function testWhatIsSentHasTheVisitsOfTheMonthPerPage()
    {
        $result = (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', false, 'the-id'))->send();

        $payload = json_decode($result['payload'], true);
        $visits = [];
        foreach ($payload['sources'] as $source) {
            $visits[$source['sourceUrl']] = array_sum(array_column($source['statistics'], 'nbVisits'));
            $this->assertSame(['PC'], array_unique(array_column($source['statistics'], 'deviceType')));
        }
        ksort($visits);

        $this->assertSame('the-id', $payload['uniqueId']);
        $this->assertSame(2, $payload['nbEntries']);
        $this->assertSame(['example.org/permits' => 1, 'example.org/services' => 3], $visits);
        $this->assertSame(['startDate' => '2024-03-01T00:00:00.00Z', 'endDate' => '2024-03-31T00:00:00.00Z'], $payload['referencePeriod']);
    }

    /**
     * @dataProvider getEnvironments
     */
    public function testTheEnvironmentDecidesThePortalAndTheKey(string $environment, string $url, string $key)
    {
        $settings = new SystemSettings();
        $settings->environment->setValue([$environment]);
        $settings->save();

        $config = (new GetAPIConfig(['idsite' => 1]))->get();

        $this->assertSame($url, $config['url']);
        $this->assertSame($key, $config['key']);
        $this->assertSame(1, $config['idsite']);
    }

    public function getEnvironments(): array
    {
        return [
            'production' => ['live', 'https://collect.youreurope.europa.eu', 'the-live-key'],
            'acceptance' => ['acceptance', 'https://collect.sdgacceptance.eu', 'the-acceptance-key'],
        ];
    }

    public function testProductionIsTheEnvironmentWhenNothingIsSet()
    {
        $this->assertSame('https://collect.youreurope.europa.eu', (new GetAPIConfig(['idsite' => 1]))->get()['url']);
    }
}
