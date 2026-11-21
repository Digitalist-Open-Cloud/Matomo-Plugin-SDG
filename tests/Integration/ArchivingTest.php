<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\API\Request;

/**
 * @group SDG
 * @group Plugins
 */
class ArchivingTest extends SdgTestCase
{
    public function testOnlyVisitsToTheReportUrlsOfTheWebsiteAreArchived()
    {
        $this->configure(1, ['reportUrls' => 'example.org/services, example.org/permits']);
        $this->configure(2, ['reportUrls' => 'example.org/other']);
        $this->trackVisit(1, ['https://example.org/services/moving', 'https://example.org/other/page', 'https://example.org/about']);
        $this->trackVisit(1, ['https://example.org/permits']);

        $this->assertEqualsCanonicalizing(['example.org/services/moving', 'example.org/permits'], $this->getArchivedUrls(1));
    }

    public function testNothingIsArchivedWithoutReportUrls()
    {
        $this->configure(2, ['reportUrls' => 'example.org']);
        $this->trackVisit(1, ['https://example.org/services/moving']);

        $this->assertSame([], $this->getArchivedUrls(1));
    }

    public function testAReportUrlIsMatchedAsTextAndNotAsPartOfTheQuery()
    {
        $this->configure(1, ['reportUrls' => "example.org/it's,example.org/a_b,example.org/100%,') OR 1=1 -- ,example.org/(x"]);
        $this->trackVisit(1, ["https://example.org/it's", 'https://example.org/a_b', 'https://example.org/axb', 'https://example.org/100', 'https://example.org/about']);

        $this->assertEqualsCanonicalizing(["example.org/it's", 'example.org/a_b'], $this->getArchivedUrls(1));
    }

    /**
     * @return string[]
     */
    private function getArchivedUrls(int $idSite): array
    {
        $report = Request::processRequest('SDG.getSingleDigitalGateway', ['idSite' => $idSite, 'period' => 'day', 'date' => self::DAY]);

        return $report->getColumn('url');
    }
}
