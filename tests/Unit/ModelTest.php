<?php

namespace Piwik\Plugins\SDG\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\SDG\Model\Period;
use Piwik\Plugins\SDG\Model\Source;
use Piwik\Plugins\SDG\Model\Statistics;
use Piwik\Plugins\SDG\Model\StatisticsPayload;

/**
 * @group SDG
 * @group Plugins
 */
class ModelTest extends TestCase
{
    /**
     * @dataProvider getCountries
     */
    public function testTheCountryIsSentAsThePortalNamesIt(string $country, string $expected)
    {
        $this->assertSame($expected, (new Statistics(1, $country, 0))->originatingCountry);
    }

    public function getCountries(): array
    {
        return [
            'in capitals' => ['se', 'SE'],
            'Greece has another code at Eurostat' => ['gr', 'EL'],
            'unknown' => ['xx', 'XX'],
        ];
    }

    /**
     * @dataProvider getDeviceTypes
     */
    public function testTheDeviceTypeIsSentAsThePortalNamesIt($deviceType, string $expected)
    {
        $this->assertSame($expected, (new Statistics(1, 'se', $deviceType))->deviceType);
    }

    public function getDeviceTypes(): array
    {
        return [
            'desktop' => [0, 'PC'],
            'smartphone' => [1, 'Smartphone'],
            'tablet' => [2, 'Tablet'],
            'as the database returns it' => ['1', 'Smartphone'],
            'a console' => [4, 'Others'],
            'not detected' => [null, 'Others'],
        ];
    }

    public function testThePayloadHasTheFormatOfThePortal()
    {
        $source = new Source('example.org/services');
        $source->addStatistics(new Statistics(3, 'se', 0));
        $source->addStatistics(new Statistics(1, 'gr', 1));

        $payload = new StatisticsPayload('the-id', new Period('2024-03-01', '2024-03-31'), [$source, new Source('example.org/permits')], null, '2024-04-02T08:00:00.00Z');

        $this->assertSame([
            'uniqueId' => 'the-id',
            'referencePeriod' => ['startDate' => '2024-03-01T00:00:00.00Z', 'endDate' => '2024-03-31T00:00:00.00Z'],
            'transferDate' => '2024-04-02T08:00:00.00Z',
            'transferType' => 'API',
            'nbEntries' => 2,
            'sources' => [
                [
                    'sourceUrl' => 'example.org/services',
                    'statistics' => [
                        ['nbVisits' => 3, 'originatingCountry' => 'SE', 'deviceType' => 'PC'],
                        ['nbVisits' => 1, 'originatingCountry' => 'EL', 'deviceType' => 'Smartphone'],
                    ],
                ],
                ['sourceUrl' => 'example.org/permits', 'statistics' => []],
            ],
        ], json_decode(json_encode($payload), true));
    }
}
