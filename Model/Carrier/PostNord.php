<?php

namespace Salecto\PostNord\Model\Carrier;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;
use Salecto\PostNord\Api\Carrier\PostNordInterface;
use Salecto\PostNord\Api\Data\ParcelShopInterface;
use Salecto\PostNord\Model\Api;
use Salecto\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Salecto\Shipping\Model\Carrier\AbstractCarrier;
use Salecto\Shipping\Model\RateManagement;

class PostNord extends AbstractCarrier implements PostNordInterface
{
    public $_code = self::TYPE_NAME;

    /**
     * @var Api
     */
    private $postNordApi;

    /**
     * @return bool
     */
    public function isActive()
    {
        if (!$this->getApiKey()) {
            return false;
        }

        return parent::isActive();
    }

    /**
     * @return false|string
     */
    public function getApiKey()
    {
        return $this->getConfigData('api_key');
    }

    /**
     * Type name that links to the Rate model
     *
     * @return string
     */
    function getTypeName(): string
    {
        return static::TYPE_NAME;
    }

    /**
     * @param string $country
     * @param string $postcode
     * @param int $amount
     * @return ParcelShopInterface[]
     */
    public function getParcelShops($country, $postcode = null, $amount = 30)
    {
        if (empty($postcode)) {
            return [];
        }

        try {
            $parcelShops = $this->getPostNordApi()->getParcelShops($country, $postcode, $amount);
        } catch (Exception $e) {
            return [];
        }

        if (empty($parcelShops) || !$parcelShops) {
            return [];
        }

        return $parcelShops;
    }

    /**
     * @return Api
     */
    public function getPostNordApi()
    {
        if (!$this->postNordApi) {
            $this->postNordApi = ObjectManager::getInstance()->create(
                Api::class,
                [
                    'apiKey' => $this->getApiKey(),
                    'sandbox' => $this->isSandbox()
                ]
            );
        }

        return $this->postNordApi;
    }

    public function isSandbox()
    {
        return (bool)$this->getConfigData('test_mode');
    }

    /**
     * @inheritDoc
     */
    public function getImageUrl(ShippingMethodInterface $shippingMethod, Rate $rate, $typeHandler)
    {
        return $this->assetRepository->createAsset('Salecto_PostNord::images/postnord.svg', [
            'area' => Area::AREA_FRONTEND
        ])->getUrl();
    }
}
