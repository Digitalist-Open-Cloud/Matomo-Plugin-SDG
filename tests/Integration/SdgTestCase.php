<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\SDG\MeasurableSettings;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * A Matomo with two websites and an SDG portal the test answers for.
 */
abstract class SdgTestCase extends IntegrationTestCase
{
    protected const DAY = '2024-03-05';

    /**
     * The requests that were sent to the portal.
     *
     * @var array<array<string, mixed>>
     */
    protected $requests = [];

    /**
     * @var int
     */
    private $visits = 0;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        Fixture::createWebsite('2024-01-01 00:00:00');
        Fixture::createWebsite('2024-01-01 00:00:00');
        Db::exec('DELETE FROM ' . Common::prefixTable('sdg'));
        $this->requests = [];
        $this->visits = 0;
    }

    /**
     * @param array<string, string> $values the settings of the website by the name of their property
     */
    protected function configure(int $idSite, array $values): void
    {
        $settings = new MeasurableSettings($idSite);
        foreach ($values as $name => $value) {
            $settings->$name->setValue($value);
        }
        $settings->save();
    }

    /**
     * Makes the portal answer every request with this.
     */
    protected function answer(int $status, string $body = ''): void
    {
        Piwik::addAction('Http.sendHttpRequest', function ($url, $params, &$response, &$responseStatus) use ($status, $body) {
            $this->requests[] = ['url' => $url] + $params;
            $response = $body;
            $responseStatus = $status;
        });
    }

    /**
     * @return array<array<string, mixed>> the transfers that were recorded
     */
    protected function getTransfers(): array
    {
        return Db::fetchAll('SELECT uniqueid, idSite, status, nbEntries FROM ' . Common::prefixTable('sdg') . ' ORDER BY id');
    }

    /**
     * @param string[] $urls
     */
    protected function trackVisit(int $idSite, array $urls, string $day = self::DAY): void
    {
        $tracker = Fixture::getTracker($idSite, $day . ' 10:00:00', true, true);
        $tracker->setTokenAuth(Fixture::getTokenAuth());
        $tracker->setNewVisitorId();
        // Matomo takes requests from one address for one visit.
        $tracker->setIp('10.0.0.' . ++$this->visits);

        foreach ($urls as $seconds => $url) {
            $tracker->setForceVisitDateTime(date('Y-m-d H:i:s', strtotime($day . ' 10:00:00 UTC') + $seconds * 60));
            $tracker->setUrl($url);
            Fixture::checkResponse($tracker->doTrackPageView('Page ' . $seconds));
        }
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

        $fixture->extraPluginsToLoad = ['SDG'];
    }
}
