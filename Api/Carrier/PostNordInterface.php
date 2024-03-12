<?php

namespace Salecto\PostNord\Api\Carrier;

use Salecto\Shipping\Api\Carrier\CarrierInterface;

interface PostNordInterface extends CarrierInterface
{
    const TYPE_NAME = 'postnord';

    /**
     * @param string $country
     * @param string $postcode
     * @param int $amount
     * @return \Salecto\PostNord\Api\Data\ParcelShopInterface[]
     */
    public function getParcelShops($country, $postcode = null, $amount = 30);
}
