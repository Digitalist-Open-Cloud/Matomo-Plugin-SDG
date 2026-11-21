<?php

namespace Piwik\Plugins\SDG\tests\Integration;

use Piwik\Plugins\SDG\Service\SOIS;
use Piwik\Plugins\SDG\Service\Sql;

/**
 * @group SDG
 * @group Plugins
 */
class SendTest extends SdgTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configure(1, ['apiKey' => 'the-key-of-site-1', 'reportUrls' => 'example.org/services']);
    }

    public function testStatisticsAreSentWithTheKeyOfTheWebsiteAndTheAnswerIsNotPrinted()
    {
        $this->answer(200, '{"answer": "of the portal"}');
        $this->expectOutputString('');

        $result = (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true, 'uuid-1'))->send();

        $this->assertSame(200, $result['status']);
        $this->assertCount(1, $this->requests);
        $this->assertSame('https://collect.youreurope.europa.eu/v2/statistics/information-services', $this->requests[0]['url']);
        $this->assertSame('POST', $this->requests[0]['httpMethod']);
        $this->assertContains('x-api-key: the-key-of-site-1', $this->requests[0]['headers']);
        $this->assertSame('uuid-1', json_decode($this->requests[0]['body'], true)['uniqueId']);
        $this->assertEquals([['uniqueid' => 'uuid-1', 'idSite' => 1, 'status' => 200, 'nbEntries' => 0]], $this->getTransfers());
    }

    public function testATransferThatIsRefusedIsRecordedAndReplacedWhenItIsSentAgain()
    {
        $this->answer(403);
        $result = (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true, 'uuid-1'))->send();

        $this->assertSame(403, $result['status']);
        $this->assertEquals(403, $this->getTransfers()[0]['status']);

        $this->answer(200);
        (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true, 'uuid-1'))->send();

        $this->assertEquals([['uniqueid' => 'uuid-1', 'idSite' => 1, 'status' => 200, 'nbEntries' => 0]], $this->getTransfers());
    }

    public function testTheUniqueIdComesFromThePortalWhenNoneIsGiven()
    {
        $this->answer(200, '"id-of-the-portal"');

        (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true))->send();

        $this->assertSame('https://collect.youreurope.europa.eu/v1/unique-id', $this->requests[0]['url']);
        $this->assertSame('id-of-the-portal', $this->getTransfers()[0]['uniqueid']);
    }

    public function testNothingIsSentWhenThePortalGivesNoUniqueId()
    {
        $this->answer(403, '{"message": "Forbidden"}');

        try {
            (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', true))->send();
            $this->fail('The transfer went on without a unique ID.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('unique ID', $e->getMessage());
        }

        $this->assertCount(1, $this->requests);
        $this->assertSame([], $this->getTransfers());
    }

    public function testNothingIsSentWithoutTheSendOption()
    {
        $this->answer(200);

        $result = (new SOIS(['idsite' => 1], '2024-03-01', '2024-03-31', false, 'uuid-1'))->send();

        $this->assertSame('dump', $result['status']);
        $this->assertSame([], $this->requests);
        $this->assertSame([], $this->getTransfers());
    }

    public function testWhatIsRecordedIsStoredAsItIs()
    {
        $uniqueId = "a', 'x', 1, now(), now(), (SELECT 200), 1, now()) -- ";

        (new Sql())->insertRequestInfo($uniqueId, "it's", 1, '2024-03-01', '2024-03-31', 200, 3, '2024-04-02');

        $this->assertEquals([['uniqueid' => $uniqueId, 'idSite' => 1, 'status' => 200, 'nbEntries' => 3]], $this->getTransfers());
    }
}
