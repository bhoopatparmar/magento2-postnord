<?php

namespace Salecto\PostNord\Model;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\Serialize\Serializer\Json;
use Salecto\PostNord\Api\Data\ParcelShopInterfaceFactory as ParcelShopFactory;

class Api
{
    const PROD_BASE_URL = 'https://api2.postnord.com/';
    const SANDBOX_BASE_URL = 'https://atapi2.postnord.com/';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var ParcelShopFactory
     */
    private $parcelShopFactory;

    /**
     * @var bool
     */
    private $sandbox;

    /**
     * @var array
     */
    private $addressMapping = [
        'delivery_address',
        'visiting_address'
    ];

    /**
     * @param ClientFactory $clientFactory
     * @param Json $jsonSerializer
     * @param string $apiKey
     * @param ParcelShopFactory $parcelShopFactory
     * @param bool $sandbox
     */
    public function __construct(
        ClientFactory $clientFactory,
        Json $jsonSerializer,
        ParcelShopFactory $parcelShopFactory,
        $apiKey = null,
        $sandbox = false
    ) {
        $this->clientFactory = $clientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->apiKey = $apiKey;
        $this->parcelShopFactory = $parcelShopFactory;
        $this->sandbox = $sandbox;
    }

    /**
     * @param       $countryCode
     * @param       $postalCode
     * @param int   $limit
     * @param array $extra This contains extra fields defined from https://developer.postnord.com/api/docs/location#/ (findNearestByAddress)
     *
     * @return array|false
     */
    public function getParcelShops($countryCode, $postalCode, $limit, $extra = [])
    {
        return $this->request(function (Client $client) use ($countryCode, $postalCode, $limit, $extra) {
            $queryData = [
                'query' => [
                    'postalCode' => $postalCode,
                    'countryCode' => $countryCode,
                    'numberOfServicePoints' => $limit,
                    'apikey' => $this->apiKey
                ]
            ];

            if (!empty($extra)) {
                $queryData['query'] = array_merge($queryData['query'], $extra);
            }
            return $client->get("rest/businesslocation/v1/servicepoint/findNearestByAddress.json", $queryData);
        }, function (Response $response, $content) {
            $content = $this->mapContent($content, 'servicePointInformationResponse/servicePoints');
            if ($content) {
                return $this->mapParcelShops($content);
            }

            return false;
        });
    }

    /**
     * @param callable $func
     * @param callable $transformer
     * @return mixed
     */
    public function request(callable $func, callable $transformer = null)
    {
        /** @var Response $response */
        $response = $func($client = $this->getClient());

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
            $content = $this->jsonSerializer->unserialize($response->getBody()->__toString());
            return $transformer === null ? $content : $transformer($response, $content);
        }

        return false;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if ($this->client === null) {
            $url = static::PROD_BASE_URL;
            if ($this->sandbox) {
                $url = static::SANDBOX_BASE_URL;
            }

            $this->client = $this->clientFactory->create([
                'config' => [
                    'base_uri' => $url,
                    'time_out' => 2.0,
                    'query' => [
                        'apikey' => $this->apiKey,
                        'returntype' => 'json'
                    ]
                ]
            ]);
        }

        return $this->client;
    }

    protected function mapContent($content, $keyPath)
    {
        $data = $content;
        foreach (explode('/', $keyPath) as $key) {
            if (is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } else {
                return null;
            }
        }
        return $data;
    }

    protected function mapParcelShops($parcelShops)
    {
        return array_map(function ($parcelShop) {
            $parcelShopData = $this->mapParcelShopData($parcelShop);

            return $this->parcelShopFactory->create([
                'data' => $parcelShopData
            ]);
        }, $parcelShops);
    }

    protected function mapParcelShopData($parcelShop, $addressMapped = false)
    {
        $parcelShopData = [];
        foreach ($parcelShop as $key => $value) {
            $key = SimpleDataObjectConverter::camelCaseToSnakeCase($key);
            if (is_array($value)) {
                if (in_array($key, $this->addressMapping)) {
                    if ($addressMapped) {
                        continue;
                    } else {
                        $value = $this->mapParcelShopData($value, $addressMapped);
                        $addressMapped = true;
                        $parcelShopData += $value;
                        continue;
                    }
                }

                $value = $this->mapParcelShopData($value, $addressMapped);
                if ($key === 'coordinate') {
                    $parcelShopData['latitude'] = $value['northing'];
                    $parcelShopData['longitude'] = $value['easting'];
                    continue;
                }
            }

            $parcelShopData[$key] = $value;
        }

        return $parcelShopData;
    }
}
