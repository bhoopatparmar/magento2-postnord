<?php

namespace Salecto\PostNord\Api\Data;

interface ParcelShopInterface extends \Salecto\Shipping\Api\Data\ParcelShopInterface
{
    const NUMBER = 'service_point_id';
    const COMPANY_NAME = 'name';
    const STREET_NAME = 'street_name';
    const STREET_NUMBER = 'street_number';
    const ZIP_CODE = 'postal_code';
    const CITY = 'city';
    const COUNTRY_CODE = 'country_code';
    const LONGITUDE = 'longitude';
    const LATITUDE = 'latitude';
    const OPENING_HOURS = 'opening_hours';
}
