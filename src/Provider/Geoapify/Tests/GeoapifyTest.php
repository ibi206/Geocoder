<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Geoapify\Tests;

use Geocoder\Collection;
use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Location;
use Geocoder\Provider\Geoapify\Geoapify;
use Geocoder\Provider\Geoapify\Model\GeoapifyAddress;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class GeoapifyTest extends BaseTestCase
{

    private const API_KEY = 'babc189d4ca74148846b8025c3a92348';

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    public function testGetName()
    {
        $provider = new Geoapify($this->getMockedHttpClient(), 'username');
        $this->assertEquals('geoapify', $provider->getName());
    }

    public function testGeocodeWithLocalhostIPv4()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Geoapify provider does not support IP addresses.');

        $provider = new Geoapify($this->getMockedHttpClient(), 'username');
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithLocalhostIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Geoapify provider does not support IP addresses.');

        $provider = new Geoapify($this->getMockedHttpClient(), 'username');
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    public function testGeocodeWithUnknownCity()
    {
        $noPlacesFoundResponse = <<<'JSON'
{
    "totalResultsCount": 0,
    "Geoapify": [ ]
}
JSON;
        $provider = new Geoapify($this->getMockedHttpClient($noPlacesFoundResponse), 'username');
        $result = $provider->geocodeQuery(GeocodeQuery::create('BlaBlaBla'));

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    public function testGeocodeWithRealPlace()
    {
        if (!isset($_SERVER['GEOAPIFY_GEOCODING_KEY'])) {
            //  $this->markTestSkipped('You need to configure the Geoapify_USERNAME value in phpunit.xml');
        }

        $provider = new Geoapify($this->getHttpClient(), self::API_KEY);
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('Harrods, London')
        ///->withData()
        );

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var GeoapifyAddress $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(51.49957, $result->getCoordinates()->getLatitude(), 0.01);
        $this->assertEqualsWithDelta(-0.16359, $result->getCoordinates()->getLongitude(), 0.01);
        $this->assertEquals('United Kingdom', $result->getCountry()->getName());
        $this->assertEqualsIgnoringCase('GB', $result->getCountry()->getCode());
        $this->assertEquals('Europe/London', $result->getTimezone());
    }


}
