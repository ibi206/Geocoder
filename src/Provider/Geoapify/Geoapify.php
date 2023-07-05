<?php

namespace Geocoder\Provider\Geoapify;

use Geocoder\Collection;
use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

final class Geoapify extends AbstractHttpProvider implements Provider
{

    const GEOCODE_ENDPOINT_URL_SSL = 'https://api.geoapify.com/v1/geocode/search?apiKey=%s&text=%s';

    private string $apiKey;

    /**
     * @param ClientInterface $client An HTTP adapter
     * @param string          $apiKey An API key
     */
    public function __construct(ClientInterface $client, string $apiKey)
    {
        if (empty($apiKey)) {
            throw new InvalidCredentials('No API key provided.');
        }

        $this->apiKey = $apiKey;
        parent::__construct($client);
    }

    /**
     * @inheritDoc
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();

        // This API doesn't handle IPs
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Geoapify provider does not support IP addresses.');
        }

        $url = sprintf(self::GEOCODE_ENDPOINT_URL_SSL,
            $this->apiKey,
            rawurlencode($query->getText()));

        return $this->executeQuery($url, $query->getLocale());
    }

    private function executeQuery(string $url, string $locale = null): AddressCollection
    {
        if (null !== $locale) {
            $url .= '&'.http_build_query([
                    'lang' => $locale,
                ], '', '&', PHP_QUERY_RFC3986);
        }

        $results = [];
        $content = $this->getUrlContents($url);
        $jsonData = json_decode($content, true);
        if ($jsonData && isset($jsonData['features'])) {
            $responseFeatures = $jsonData['features'];
            foreach ($responseFeatures as $item) {
                $properties = $item['properties'];
                $builder = new AddressBuilder($this->getName());
                $builder->setCountryCode($properties['country_code']);
                $builder->setCountry($properties['country']);
                $builder->setStreetName($properties['street']);
                $builder->setStreetNumber($properties['housenumber']);
                $builder->setLocality($properties['city']);
                $builder->setPostalCode($properties['postcode']);
                $builder->setCoordinates($properties['lat'], $properties['lon']);
                $builder->setTimezone($properties['timezone']['name']);
                $results[] = $builder->build();
            }
        }

        return new AddressCollection($results);
    }

    /**
     * @inheritDoc
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        // TODO: Implement reverseQuery() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'geoapify';
    }
}
