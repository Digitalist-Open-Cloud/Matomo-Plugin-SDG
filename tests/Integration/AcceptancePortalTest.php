<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Plugins\SDG\Service\SOIS;
use Piwik\Plugins\SDG\SystemSettings;

/**
 * Sends statistics to the acceptance environment of the SDG portal.
 *
 * Needs a key for that environment in the environment variable SDG_ACCEPTANCE_API_KEY, and the
 * URL the key is registered for in SDG_ACCEPTANCE_URL. It is skipped without them.
 *
 * @group SDG
 * @group Plugins
 */
class AcceptancePortalTest extends SdgTestCase
{
    public function testStatisticsAreAcceptedByThePortal()
    {
        $key = (string) getenv('SDG_ACCEPTANCE_API_KEY');
        $url = (string) getenv('SDG_ACCEPTANCE_URL');
        if ($key === '' || $url === '') {
            $this->markTestSkipped('SDG_ACCEPTANCE_API_KEY and SDG_ACCEPTANCE_URL are not set.');
        }

        $settings = new SystemSettings();
        $settings->environment->setValue(['acceptance']);
        $settings->save();
        $this->configure(1, ['acceptanceApiKey' => $key, 'reportUrls' => $url]);
        $this->trackVisit(1, ['https://' . $url]);

        $result = (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true))->send();

        $this->assertSame(200, $result['status'], $result['payload']);
        $this->assertEquals(200, $this->getTransfers()[0]['status']);
        $this->assertEquals(1, $this->getTransfers()[0]['nbEntries']);
    }
}
